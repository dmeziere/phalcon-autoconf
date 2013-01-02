<?php
class Autoconf
{
	private $di;

	/**
	 * Note: Using $this->config in anonymous functions would be nicer, but is 
	 * uncompatible with PHP < 5.4.
	 * 
	 * @param Phalcon\Config $config
	 * @return none
	 */
	public function __construct(Phalcon\Config $config)
	{
	    /**
	     * The FactoryDefault Dependency Injector automatically register the right 
	     * services providing a full stack framework.
	     */
	    $this->di = new \Phalcon\DI\FactoryDefault();

	    $this->initPhp($config)
	    	->initLogger($config)
	    	->initSession($config)
	    	->initDatabase($config)
	    	->initModel($config)
	    	->initView($config)
	    	->initUrl($config)
	    	->initFlash($config);
    
	    return;
	}

	public function initPhp(Phalcon\Config $config)
	{
	    // Setting local php ini directives.
	    if (isset($config->ini)) {
	    	foreach ($config->ini as $directive => $value) {
	    		ini_set($directive, $value);
	    	}
	    }
		
	    return $this;
	}
	
	public function initLogger(Phalcon\Config $config)
	{
	    /**
	     * Register the logger service.
	     */
	    if ($config->logger->adapter == 'file') {
	    	$this->di->set('logger', function() use ($config) {
	    		
	    		$options = array();
	
	    		if (isset($config->logger->mode)) {
	    			$options['mode'] = $config->logger->mode;
	    		}
	    		
	    		switch ($config->logger->adapter) {
	    			
	    			case 'file':
	    				$adapter = '\Phalcon\Logger\Adapter\File';

	    				$fileName = strftime($config->logger->filename);
	    				
	    				echo(dirname($fileName));
	    				
	    				if (!file_exists(dirname($fileName))) {
	    					mkdir(dirname($fileName), 0777, true);
	    				}
	    				
			    		$logger = new $adapter(
			    			$fileName,
			    			$options
			    		);

			    		break;
	    			
    				default:
    					throw new Exception(
	    					'Logger adapter not implemented.'
	    				);
	    		}
	
	    		if (isset($config->logger->format)) {
	    			$logger->setFormat($config->logger->format);
	    		}
	    		return $logger;
	    	});
	    }
		
	    return $this;
	}

	public function initSession(Phalcon\Config $config)
	{
	    /**
	     * Start the session the first time some component request the session 
	     * service.
	     */
	    if ($config->session->adapter != 'none') {
		    $this->di->set('session', function() use ($config) {
		    	
		    	switch ($config->session->adapter) {
		    		case 'files': $adapter = 'Phalcon\Session\Adapter\Files'; break;
		        	default: throw new Exception(
		        		'Session adapter not implemented.'
		        	);
		    	}
	
		    	$session = new $adapter();
		        $session->start();
		        return $session;
		    });
	    }
		
	    return $this;
	}

	public function initDatabase(Phalcon\Config $config)
	{
	    /**
	     * Database connection is created based on the parameters defined in the 
	     * configuration file.
	     */
	    if ($config->database->adapter != 'none') {
		    $this->di->set('db', function() use ($config) {
		    	
		    	$options = array(
		            "host"     => $config->database->host,
		            "username" => $config->database->username,
		            "password" => $config->database->password,
		            "dbname"   => $config->database->name
		        );
		        
		        if (isset($config->database->persistent)) {
		        	$options['persitent'] = $config->database->persistent;
		        }
		    	
		        if (isset($config->database->schema)) {
		        	$options['schema'] = $config->database->schema;
		        }
		    	
		        switch ($config->database->adapter) {
		        	case 'mysql': $adapter = '\Phalcon\Db\Adapter\Pdo\Mysql'; break;
		        	case 'pgsql': $adapter = '\Phalcon\Db\Adapter\Pdo\Postgresql'; break;
		        	case 'sqlite': $adapter = '\Phalcon\Db\Adapter\Pdo\Sqlite'; break;
		        	default: throw new Exception('Db adapter not implemented.');
		        }
		        
		        $db = new $adapter($options);
		        return $db;
		    });
	    }

	    return $this;
	}

	public function initModel(Phalcon\Config $config)
	{
	    /**
	     * If the configuration specify the use of metadata adapter use it or use 
	     * memory otherwise.
	     */
	    $this->di->set('modelsMetadata', function() use ($config) {
	        if (isset($config->models->metadata)) {
	            $metaDataConfig = $config->models->metadata;
	            $metadataAdapter = 'Phalcon\Mvc\Model\Metadata\\'.$metaDataConfig->adapter;
	            return new $metadataAdapter();
	        } else {
	            return new Phalcon\Mvc\Model\Metadata\Memory();
	        }
	    });
		
	    return $this;
	}

	public function initView(Phalcon\Config $config)
	{
	    if (isset($config->view->viewsDir)) {
		    $this->di->set('view', function() use ($config) {
		        $view = new \Phalcon\Mvc\View();
		        $view->setViewsDir($config->view->viewsDir);
		        
		        if ($config->view->useVolt) {
		        	$view->registerEngines(array(
		            	'.volt' => 'Phalcon\Mvc\View\Engine\Volt'
	            	));
		        }

   		        if ($config->view->usePhp) {
		        	$view->registerEngines(array(
		            	'.phtml' => 'Phalcon\Mvc\View\Engine\Php'
	            	));
		        }

		        return $view;
		    });
	    }
		
	    return $this;
	}
	
	public function initUrl(Phalcon\Config $config)
	{
	    /**
	     * The URL component is used to generate all kind of urls in the 
	     * application.
	     */
	    if (isset($config->application->baseUri)) {
		    $this->di->set('url', function() use ($config) {
		        $url = new \Phalcon\Mvc\Url();
		        $url->setBaseUri($config->application->baseUri);
		        return $url;
		    });
	    }
		
	    return $this;
	}

	public function initFlash(Phalcon\Config $config)
	{
	    /**
	     * Register the flash service with custom CSS classes.
	     */
	    if ($config->flash->adapter != 'none') {
	    	$this->di->set('flash', function() use ($config) {
		    	
		    	$options = array(
		            'error'   => $config->flash->error,
		            'success' => $config->flash->success,
		            'notice'  => $config->flash->notice,
		        );
		        
		    	switch ($config->flash->adapter) {
		    		case 'direct': $adapter = 'Phalcon\Flash\Direct'; break;
		        	default: throw new Exception('Flash adapter not implemented.');
		    	}
		        
		    	$flash = new $adapter($options);
		    	return $flash;
		    });
	    }

	    return $this;
	}

	public function getDi()
	{
		return $this->di;
	}
}