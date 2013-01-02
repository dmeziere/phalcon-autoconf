Phalcon autoconf
================

Phalcon PHP is a web framework delivered as a C extension providing high performance and lower resource consumption.

This is a library and related config file for the Phalcon PHP framework 0.7.0. It's will is to allow a beter control over application bootstrap.

This project is stil not stable, it currently handles :
* PHP ini_set() definitions
* Phalcon\Logger
* Phalcon\Session
* Phalcon\Db
* Phalcon\Mvc\Model
* Phalcon\Mvc\View
* Phalcon\Mvc\Url
* Phalcon\Flash

Get started
-----------

To use Autoconf you should place the config file in your config folder (ie: app/config/) and use the class in your public/index.php :

This class expect the current directory to be set to application root.

    define('DS', DIRECTORY_SEPARATOR);
    
    chdir(__DIR__.DS.'..'.DS);

Then we initialize the application as usually.

    try {
        
        //Read the configuration
        $config = new Phalcon\Config\Adapter\Ini(
            'app'.DS.'config'.DS.'config.ini'
        );
        
        $loader = new \Phalcon\Loader();
        
        /**
         * We're a registering a set of directories taken from the configuration 
         * file
         */
        $loader->registerDirs(
            array(
                $config->application->controllersDir,
                $config->application->pluginsDir,
                $config->application->libraryDir,
                $config->application->modelsDir,
            )
        )->register();
        
Autoconf will need the directories to be defined and will return the dependency injector.

        $autoconf = new Autoconf($config);
        $di = $autoconf->getDi();
        
Here you can handle plugins, events, etc
        
        $application = new \Phalcon\Mvc\Application();
        $application->setDI($di);
        echo $application->handle()->getContent();
    } catch (Phalcon\Exception $e) {
        echo $e->getMessage();
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

Don't forget to cusomize app/config/config.ini to your needs.
