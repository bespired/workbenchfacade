<?php
namespace Bespired\Workbenchfacade\Commands;


use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use File;

class WorkbenchFacade extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'workbench:facade';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Adds a facade in the workbench package.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{

		
		$package = $this->argument('package');
		$route   = $this->providerNameFromPackageName( $package );

		$this->checkFacadeExists( $route );
		$this->createFacadeFolder( $route );
		
		$this->createFacadePhp( $route );
		$this->adjustServiceProvider( $route );
		
		$this->createClassPhp( $route );
		

	}

	private function checkFacadeExists( $route )
	{
		if ( File::exists( $route['mkdir'] ))
		{
			$this->info( "- Facade already exists." );
			exit;
		}
		return;
	}

	private function createFacadeFolder( $route )
	{

		$result = File::makeDirectory( $route['mkdir'] );
		if (!$result)
		{
			$this->error( "- Could not create folder for " . $route['name'] );
			exit;
		}
		return;
	}

	private function createFacadePhp( $route )
	{

		$facade_txt = File::get( __DIR__ . '/stubs/facade.txt');

		$seach   = ['$namespace', '$shortname', '$classname'];
		$replace = [$route['namespace'], $route['shortname'], $route['classname'] ];

		$facade_txt = str_replace( $seach, $replace, $facade_txt );

		$result = File::put( $route['facade_php'] , $facade_txt );

		
	}


	private function adjustServiceProvider( $route )
	{
		$service_txt = File::get( $route['service'] );

		if ( strpos( $service_txt, 'function boot()' ) === false )
		{
			$this->adjustNoBootProvider( $route );
			return;
		}

		$this->adjustBootProvider( $route );

	}


	private function adjustBootProvider( $route )
	{

		$service_txt = File::get( $route['service'] );

		$re = "/(public function boot\\(\\))\\n\\s+\\{\\n(.*\\n)*?(\\s+\\})/"; 

		$boot_txt = File::get( __DIR__ . '/stubs/providerboot.txt');

		$seach   = [ '$package', '$shortname', '$classname', '$facadepath' ];
		$replace = [ $route['vendor_package'], $route['shortname'], $route['classname'], $route['facadepath'] ];

		$boot_txt = str_replace( $seach, $replace, $boot_txt );

		$subst = "$1\n\t{\n\t$boot_txt\n\t}";
		$service_txt = preg_replace($re, $subst, $service_txt, 1);

		$result = File::put( $route['service'] , $service_txt );

	}


	private function adjustNoBootProvider( $route )
	{

		$service_txt = File::get( $route['service'] );

		$re = '/((protected \\$defer)\\s*\\=?\\s*(false|true);)/i'; 
		preg_match($re, $service_txt, $matches);
		if ( !$matches[0] )
		{
			$this->error( "- Cannot find place to insert the boot function." );
			exit;
		}

		$boot_txt = File::get( __DIR__ . '/stubs/providerboot.txt');

		$seach   = [ '$package', '$shortname', '$classname', '$facadepath' ];
		$replace = [ $route['vendor_package'], $route['shortname'], $route['classname'], $route['facadepath'] ];

		$boot_txt = str_replace( $seach, $replace, $boot_txt );

		$subst = "$1\n\n$boot_txt"; 
		$service_txt = preg_replace($re, $subst, $service_txt, 1);

		$result = File::put( $route['service'] , $service_txt );

	}

	private function createClassPhp( $route )
	{
		if ( File::exists( $route['class_php'] ))
		{
			$this->info( "- Class ".$route['class_php']." already exists." );
			exit;
		}

		$class_txt = File::get( __DIR__ . '/stubs/class.txt');
		$seach   = ['$namespace', '$classname'];
		$replace = [$route['namespace'], $route['classname'] ];
		$class_txt = str_replace( $seach, $replace, $class_txt );

		$result = File::put( $route['class_php'], $class_txt );

		return;
	}



	// 
	// 	example:
 	//  package:  centagon/topdf
 	//  provider: Centagon\ToPdf\ToPdfServiceProvider
 	//  --note the case in ToPdf ...
 	//

	private function providerNameFromPackageName( $vendor_package )
	{
		
		$parts   = explode( '/', $vendor_package );
		
		$vendor    = file_exists( base_path() . '/vendor/' . $vendor_package );
		$workbench = file_exists( base_path() . '/workbench/' . $vendor_package );

		if (( $workbench ) or ( $vendor ))
		{
			if ( $vendor )
				$root = base_path() . '/vendor/' . $vendor_package . '/src/'; 
			else
				$root = base_path() . '/workbench/' . $vendor_package . '/src/'; 


			$dir = scandir( $root );
			$low = array_map('strtolower', $dir);
			if ( !in_array( $parts[0], $low ) ) return '';

			$vendor = $dir[ array_search( $parts[0], $low )];
			$root .= $vendor . '/';

			$dir = scandir( $root );
			$low = array_map('strtolower', $dir);
			if ( !in_array( $parts[1], $low ) ) return '';

			$package = $dir[ array_search( $parts[1], $low )];
			$root .= $package . '/';
			
			$dir = scandir( $root );
			$low = array_map('strtolower', $dir);
			$service = $parts[1] . 'serviceprovider.php';
			if ( !in_array( $service, $low ) ) return '';

			$provider = substr( $dir[ array_search( $service, $low )], 0, -4 );

			return [
				'vendor_package' => $vendor_package,
				'path'           => $vendor . '/' . $package . '/',
				'name'           => $provider,
				'service'        => $root . '/' . $provider . '.php',
				'mkdir'          => $root . 'Facade' . '/',
				'facade_name'    => $root . 'Facade' . '/' . $package,
				'facade_php'     => $root . 'Facade' . '/' . $package . '.php',
				'class_php'      => $root . $package . '.php',
				'namespace'      => $vendor . '\\' . $package . '\\' . 'Facades',
				'shortname'      => strtolower( $package ),
				'facadepath'     => $vendor . '\\' . $package . '\\' . 'Facades' . '\\' . $package,
				'classname'      => $package,

			];

		}
		
		return '';		

	}

	private function listProviders( $providers, $me )
	{
		$this->info( "" );
		foreach ($providers as $key => $provider) {
			if ( substr( $provider, 0, 10 ) !== 'Illuminate' )
			{
				if ( $provider == $me )
					$this->info( "- $provider" );
				else	
					$this->line( "- $provider" );
			}
		}
		$this->info( "" );
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('package', InputArgument::REQUIRED, 'vendor/package'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('silent', null, InputOption::VALUE_OPTIONAL, 'bverbose', null),
		);
	}

}
