#!/usr/bin/env php
<?php
/**
 * octofoo is a light and easy static website generator written in PHP.
 * @see http://octofoo.narno.org
 *
 * @author Arnaud Ligny <arnaud@ligny.org>
 * @license The MIT License (MIT)
 *
 * Copyright (c) 2013-2014 Arnaud Ligny
 */

namespace
{
   error_reporting(E_ALL ^ E_NOTICE);
   date_default_timezone_set("UTC");

   use Zend\Console\Console as Console;
   use Zend\Console\Getopt;
   use Zend\Console\Exception\RuntimeException as ConsoleException;

   // Composer autoloading
   if (file_exists(__DIR__ . '/vendor/autoload.php')) {
      $loader = include __DIR__ . '/vendor/autoload.php';
   }
   else {
      echo 'Run the following commands:' . PHP_EOL;
      if (!file_exists('composer.json')) {
         echo 'curl https://raw.github.com/Narno/OctoFoo/master/composer.json > composer.json' . PHP_EOL;
      }
      if (!file_exists('composer.phar')) {
         echo 'curl -s http://getcomposer.org/installer | php' . PHP_EOL;
      }
      echo 'php composer.phar install' . PHP_EOL;
      exit(2);
   }

   try {
      $console = Console::getInstance();
      $octofooConsole = new OctoFoo\Console($console);
   } catch (ConsoleException $e) {
      //echo "Could not get console adapter - most likely we are not running inside a console window.";
   }

   if (version_compare(PHP_VERSION, '5.4.0', '<')) {
      $octofooConsole->wlError('PHP 5.4+ required (your version: ' . PHP_VERSION . ')');
      exit(2);
   }

   define('DS', DIRECTORY_SEPARATOR);
   define('OCTOFOO_DIRNAME', '_octofoo');
   $websitePath = '';//getcwd();

   // Defines rules
   $rules = array(
      'help|h'     => 'Get OctoFoo usage message',
      'generate|g' => 'Generate static files',
      'serve|s'    => 'Start built-in web server',
      'deploy|d'   => 'Deploy static files',
      'list|l'     => 'Lists content',
   );

   // Get and parse console options
   try {
      $opts = new Getopt($rules);
      $opts->parse();
   } catch (ConsoleException $e) {
      echo $e->getUsageMessage();
      exit(2);
   }

   // help option
   if ($opts->getOption('help') || count($opts->getOptions()) == 0) {
      echo $opts->getUsageMessage();
      exit(0);
   }

   // Get provided directory if exist
   if (!isset($opts->getRemainingArgs()[0])) {
      $path = '.';
   }
   else {
      $path = $opts->getRemainingArgs()[0];
   }
   if (!is_dir($path)) {
      $octofooConsole->wlError('Invalid directory provided!');
      exit(2);
   }
   $websitePath = str_replace(DS, '/', realpath($path));

   // Instanciate the OctoFoo API
   try {
      $octofoo = new OctoFoo\OctoFoo($websitePath);
   } catch (\Exception $e) {
      $octofooConsole->wlError($e->getMessage());
      exit(2);
   }

   // generate option
   if ($opts->getOption('generate')) {
      $serveConfig = array();
      $octofooConsole->wlInfo('Generate website');
      if (isset($opts->serve)) {
         $octofoo->setLocalServe(true);
         $octofooConsole->wlInfo('You should re-generate before deploy');
      }
      try {
         $octofoo->loadPages()->generate();
         $messages = $octofoo->getMessages();
         foreach ($messages as $message) {
            $octofooConsole->wlDone($message);
         }
      } catch (\Exception $e) {
         $octofooConsole->wlError($e->getMessage());
      }
   }

   // serve option
   if ($opts->getOption('serve')) {
      if (!is_file(sprintf('%s/%s/router.php', $websitePath, OCTOFOO_DIRNAME))) {
         $octofooConsole->wlError('Router not found');
         exit(2);
      }
      $octofooConsole->wlInfo(sprintf("Start server http://%s:%d", '0.0.0.0', '8000'));
      if (OctoFoo\Utils\isWindows()) {
         $command = sprintf(
            'START php -S %s:%d -t %s %s > nul',
            '0.0.0.0',
            '8000',
            $websitePath . '/_octofoo_static_site',
            sprintf('%s/%s/router.php', $websitePath, OCTOFOO_DIRNAME)
         );
      }
      else {
         echo 'Ctrl-C to stop it.' . PHP_EOL;
         $command = sprintf(
            //'php -S %s:%d -t %s %s >/dev/null',
            'php -S %s:%d -t %s %s',
            '0.0.0.0',
            '8000',
            $websitePath . '/_octofoo_static_site',
            sprintf('%s/%s/router.php', $websitePath, OCTOFOO_DIRNAME)
         );
      }
      exec($command);
   }

   // deploy option
   if ($opts->getOption('deploy')) {
      $octofooConsole->wlInfo('Deploy website on GitHub');
      try {
         $config = $octofoo->getConfig();
         if (!isset($config['deploy']['repository']) && !isset($config['deploy']['branch'])) {
            throw new \Exception('Cannot find the repository name in the config file');
         }
         else {
            $repoUrl = $config['deploy']['repository'];
            $repoBranch = $config['deploy']['branch'];
         }
         $deployDir = $octofoo->getWebsitePath() . '/../.' . basename($octofoo->getWebsitePath());
         if (is_dir($deployDir)) {
            //echo 'Deploying files to GitHub...' . PHP_EOL;

            $it = new RecursiveDirectoryIterator($deployDir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,
               RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
               //echo "Deleting: " . $file->getRealPath() . "\n";
               if ($file->isDir()){
                  @rmdir($file->getRealPath());
               } else {
                  @unlink($file->getRealPath());
               }
            }


            /*$deployFileIterator = new FilesystemIterator($deployDir);
            foreach ($deployFileIterator as $deployFile) {
               if ($deployFile->isFile()) {
                  echo "Deleting File: $deployFile\n";
                  @unlink($deployFile->getPathname());
               }
            }
            sleep(2);
            $deployDirIterator = new FilesystemIterator($deployDir);
            foreach ($deployDirIterator as $deployDir) {
               if ($deployDir->isDir() && $deployDir->getFilename() != '.git') {
                  echo "Deleting Dir: $deployDir\n";
                  OctoFoo\Utils\RecursiveRmDir($deployDir->getPathname());
               }
            }
            sleep(2);
             */

            OctoFoo\Utils\RecursiveCopy($octofoo->getWebsitePath() . '/_octofoo_static_site' , $deployDir);
            $updateRepoCmd = array(
               'add -A',
               'commit -m "Update ' . $repoBranch . ' via OctoFoo"',
               'push github ' . $repoBranch . ' --force'
            );
            OctoFoo\Utils\runGitCmd($deployDir, $updateRepoCmd);
         }
         else {
            //echo 'Setting up GitHub deployment...' . PHP_EOL;
            @mkdir($deployDir);
            OctoFoo\Utils\RecursiveCopy($octofoo->getWebsitePath() . '/_octofoo_static_site', $deployDir);
            $initRepoCmd = array(
               'init',
               'add -A',
               'commit -m "Create ' . $repoBranch . ' via OctoFoo"',
               'branch -M ' . $repoBranch . '',
               'remote add github ' . $repoUrl,
               'push github ' . $repoBranch . ' --force'
            );
            OctoFoo\Utils\runGitCmd($deployDir, $initRepoCmd);
         }
      } catch (\Exception $e) {
         $octofooConsole->wlError($e->getMessage());
      }
   }

   // list option
   if ($opts->getOption('list')) {
      if (isset($opts->list)) {
         // @todo list by path?
      }
      try {
         $octofooConsole->wlInfo('List content');
         $pages = $octofoo->getPagesTree();
         if ($console->isUtf8()) {
            $unicodeTreePrefix = function(RecursiveTreeIterator $tree) {
               $prefixParts = [
                  RecursiveTreeIterator::PREFIX_LEFT         => ' ',
                  RecursiveTreeIterator::PREFIX_MID_HAS_NEXT => '│ ',
                  RecursiveTreeIterator::PREFIX_END_HAS_NEXT => '├ ',
                  RecursiveTreeIterator::PREFIX_END_LAST     => '└ '
               ];
               foreach ($prefixParts as $part => $string) {
                  $tree->setPrefixPart($part, $string);
               }
            };
            $unicodeTreePrefix($pages);
         }
         $console->writeLine('[pages]');
         foreach($pages as $page) {
            $console->writeLine($page);
         }
      } catch (\Exception $e) {
         $octofooConsole->wlError($e->getMessage());
      }
   }
}

namespace OctoFoo
{
   use Zend\Console\ColorInterface as Color;
   use Zend\EventManager\EventManager;
   use Zend\Loader\PluginClassLoader;
   use Michelf\MarkdownExtra;
   use OctoFoo\Utils;

   /**
    * OctoFoo API
    */
   class OctoFoo
   {
      const VERSION = '0.0.2';
      const URL = 'http://octofoo.narno.org';
      //
      const OCTOFOO_DIRNAME = '_octofoo';
      const OUTPUT_DIRNAME = '_octofoo_static_site';
      const CONFIG_FILENAME = 'config.ini';
      const LAYOUTS_DIRNAME = 'layouts';
      const ASSETS_DIRNAME  = 'assets';
      const CONTENT_DIRNAME = 'content';
      const CONTENT_PAGES_DIRNAME = '';
      const PLUGINS_DIRNAME  = 'plugins';

      protected $_websitePath;
      protected $_websiteFileInfo;
      protected $_events;
      protected $_config = null;
      protected $_messages = array();
      protected $_pages = array();
      protected $_menu = array();
      public $localServe = false;
      protected $_processor;

      public function __construct($websitePath)
      {
         $splFileInfo = new \SplFileInfo($websitePath);
         if (!$splFileInfo->isDir()) {
            throw new \Exception('Invalid directory provided');
         }
         else {
            $this->_websiteFileInfo = $splFileInfo;
            $this->_websitePath = $splFileInfo->getRealPath();
         }
         // Load plugins
         $this->_events = new EventManager();
         $this->loadPlugins();

         // load sitemap generator
         $this->_sitemap = new Sitemap('http://hackernode.com:8000');
      }

      public function getWebsiteFileInfo()
      {
         return $this->_websiteFileInfo;
      }

      public function setWebsitePath($path)
      {
         $this->_websitePath = $path;
         return $this->getWebsitePath();
      }

      public function getWebsitePath()
      {
         return $this->_websitePath;
      }

      public function getEvents()
      {
         return $this->_events;
      }

      public function getMessages()
      {
         return $this->_messages;
      }

      public function addMessage($message)
      {
         $this->_messages[] = $message;
         return $this->getMessages();
      }

      public function clearMessages()
      {
         $this->_messages = array();
      }

      public function getPages($subDir='')
      {
         if (!empty($subDir)) {
            foreach ($this->_pages as $key => $value) {
               if (strstr($key, $subDir . '/') !== false) {
                  $tmpPages[] = $this->_pages[$key];
               }
            }
            return $tmpPages;
         }
         return $this->_pages;
      }

      public function addPage($index, $data)
      {
         $this->_pages[$index] = $data;
         return $this->getPages();
      }

      private function sortPages()
      {
         usort( $this->_pages, function ($a, $b) { if ( $a['postdate']==$b['postdate'] ) return 0; else return ($a['postdate'] > $b['postdate']) ? -1 : 1; });
      }

      public function getMenu($menu='')
      {
         if (!empty($menu) && array_key_exists($menu, $this->_menu)) {
            return $this->_menu[$menu];
         }
         return $this->_menu;
      }

      public function addMenuEntry($menu, $entry)
      {
         $this->_menu[$menu][] = $entry;
         return $this->getMenu($menu);
      }

      //public function triggerEvent($method, $args, $when=array('pre','post'))
      public function triggerEvent($method, $args, $when)
      {
         $reflector = new \ReflectionClass(__CLASS__);
         $parameters = $reflector->getMethod($method)->getParameters();
         if (!empty($parameters)) {
            $params = array();
            foreach ($parameters as $parameter) {
               $params[$parameter->getName()] = $parameter->getName();
            }
            $args = array_combine($params, $args);
         }
         $results = $this->getEvents()->trigger($method . '.' . $when, $this, $args);
         if ($results) {
            return $results->last();
         }
         return $this;
      }

      // temporay method
      public function setLocalServe($status)
      {
         return $this->localServe = $status;
      }

      private function createRouterFile()
      {
         $content = <<<'EOT'
<?php
date_default_timezone_set("UTC");
define("DIRECTORY_INDEX", "index.html");
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);
if (empty($ext)) {
    $path = rtrim($path, "/") . "/" . DIRECTORY_INDEX;
}
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $path)) {
    return false;
}
http_response_code(404);
echo "404, page not found";
EOT;
         if (!@file_put_contents($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/router.php', $content)) {
            throw new \Exception('Cannot create the router file');
         }
         return 'Router file created';
      }

      private function createRobotsTxt()
      {
         $content = <<<'EOT'
User-agent: *
Disallow:
EOT;
         if (!@file_put_contents($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/robots.txt', $content)) {
            throw new \Exception('Cannot create the robots.txt file');
         }
         return 'Robots.txt file created';
      }

      private function createReadmeFile()
      {
         $content = <<<'EOT'
Powered by [OctoFoo](http://octofoo.narno.org).
EOT;

         if (is_file($this->getWebsitePath() . '/README.md')) {
            if (!@unlink($this->getWebsitePath() . '/README.md')) {
               throw new \Exception('Cannot create the README file');
            }
         }
         if (!@file_put_contents($this->getWebsitePath() . '/README.md', $content)) {
            throw new \Exception('Cannot create the README file');
         }
         return 'README file created';
      }

      /**
       * Get config from config.ini file
       * @param  string $key
       * @return array
       */
      public function getConfig($key='')
      {
         if ($this->_config == null) {
            $configFilePath = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONFIG_FILENAME;
            if (!file_exists($configFilePath)) {
               throw new \Exception('Cannot get config file');
            }
            if (!($this->_config = parse_ini_file($configFilePath, true))) {
               throw new \Exception('Cannot parse config file');
            }
            if (!empty($key)) {
               if (!array_key_exists($key, $this->_config)) {
                  throw new \Exception(sprintf('Cannot find %s key in config file', $key));
               }
               return $this->_config[$key];
            }
            $this->_config;
         }
         return $this->_config;
      }

      /**
       * Load pages files from content/pages
       * @return object OctoFoo\OctoFoo
       */
      public function loadPages()
      {
         $pageInfo  = array();
         $pageIndex = array();
         $pageData  = array();
         //$pagesPath = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
         $pagesPath = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME;

         // Iterate pages files, filtered by markdown "md" extension
         $pagesIterator = new FileIterator($pagesPath, 'md');
         foreach ($pagesIterator as $filePage) {
            $pageInfo = $filePage->parse()->getData('info');
            //$pageIndex = isset($pageInfo['index'])?$pageInfo['index']:($pagesIterator->getSubPath() ? $pagesIterator->getSubPath() : 'home');
            $pageIndex = ($pagesIterator->getSubPathname() ? $pagesIterator->getSubPathname() : 'home');
            //echo $pageIndex . "\n";
            $pageData = $pageInfo;
            //
            $pageData['title'] = (
               isset($pageInfo['title']) && !empty($pageInfo['title'])
               ? $pageInfo['title']
               : ucfirst($filePage->getBasename('.md'))
            );
            $pageData['path'] = str_replace(DS, '/', $pagesIterator->getSubPath());
            $pageData['basename'] = $filePage->getBasename('.md') . '.html';
            $pageData['template'] = (
               isset($pageInfo['template'])
               && is_file($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/' . (isset($this->getConfig()['site']['layout']) ? $this->getConfig()['site']['layout'] . '/' : '') . $pageInfo['template'] . '.html')
               ? $pageInfo['template'] . '.html'
               : 'index.html'
            );
            //echo "Using template: " . $pageData['template'] . "\n";
            if (isset($pageInfo['pagination'])) {
               $pageData['pagination'] = $pageInfo['pagination'];
            }
            if(isset($pageInfo['tags'])) {
               $pageData['tags'] = explode(",", $pageInfo['tags']);
            }
            if(isset($pageInfo['postdate'])) {
               $pageInfo['postdate'] = strtotime($pageInfo['postdate']);
               $pageData['fulldate'] = date("jS F, Y", $pageInfo['postdate']);
               $pageData['date'] = date("F Y", $pageInfo['postdate']);
            }
            if(isset($pageInfo['images'])) {
               $pageData['images'] = explode(",", $pageInfo['images']);
            }
            // in case of external content
            if (isset($pageInfo['content']) /* && is valid URL to md file */) {
               if (false === ($pageContent = @file_get_contents($pageInfo['content'], false))) {
                  throw new \Exception(sprintf("Cannot get contents from %s\n", $filePage->getFilename()));
               }
            }
            else {
               $pageContent = $filePage->getData('content_raw');
            }
            // content processing
            $pageData['content'] = $this->process($pageContent);

            // event postloop
            $results = $this->triggerEvent(__FUNCTION__, array(
               'pageInfo'  => $pageInfo,
               'pageIndex' => $pageIndex,
               'pageData'  => $pageData
            ), 'postloop');
            if ($results) {
               extract($results);
            }

            // add page details
            $this->addPage($pageIndex, $pageData);
            // menu
            if (isset($pageInfo['menu'])) { // "nav" for example
               $menuEntry = (
                  !empty($pageInfo['menu'])
                  ? array(
                     'title' => $pageInfo['title'],
                     'path'  => str_replace(DS, '/', $pagesIterator->getSubPath())
                  )
                  : ''
               );
               $this->addMenuEntry($pageInfo['menu'], $menuEntry);
            }
            unset($pageInfo);
            unset($pageIndex);
            unset($pageData);
         }

         $this->sortPages();

         return $this;
      }

      public function process($rawContent)
      {
         // Markdown only
         $this->_processor = new MarkdownExtra;
         $this->_processor->code_attr_on_pre = true;
         $this->_processor->predef_urls = array('base_url' => $this->getConfig()['site']['base_url']);
         // [my base url][base_url]
         return $this->_processor->transform($rawContent);
      }

      /**
       * Temporary method to prepare (sort) "nav" menu
       * @return array
       */
      public function prepareMenuNav()
      {
         $menuNav = $this->getMenu('nav');
         // sort nav menu
         foreach ($menuNav as $key => $row) {
            $path[$key] = $row['path'];
         }
         if (isset($path) && is_array($path)) {
            array_multisort($path, SORT_ASC, $menuNav);
         }
         return $menuNav;
      }

      /**
       * Generate static files
       * @return array Messages
       */
      public function generate()
      {
         $pages = $this->getPages();

         // Initialize sitemap
         $this->_sitemap->setPath($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/');

         //print_r($pages);
         $menuNav = $this->prepareMenuNav();
         if (isset($this->getConfig()['site']['layout'])) {
            $tplEngine = $this->tplEngine($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/' . $this->getConfig()['site']['layout']);
         }
         else {
            $tplEngine = $this->tplEngine($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::LAYOUTS_DIRNAME);
         }
         $pagesIterator = (new \ArrayObject($pages))->getIterator();
         $pagesIterator->ksort();
         $currentPos = 0;
         $prevPos = '';
         $this->clearMessages();
         while ($pagesIterator->valid()) {
            // pagination
            $previous = $next = '';
            $prevTitle = $nextTitle = '';
            $page = $pagesIterator->current();
            if (isset($page['pagination']) && $page['pagination'] == 'enabled') {
               if ($pagesIterator->offsetExists($prevPos)) {
                  $previous = $pagesIterator->offsetGet($prevPos)['path'];
                  $prevTitle = $pagesIterator->offsetGet($prevPos)['title'];
               }
               $pagesIterator->next();
               if ($pagesIterator->valid()) {
                  if (isset($pagesIterator->current()['pagination']) && $pagesIterator->current()['pagination'] == 'enabled') {
                     $next = $pagesIterator->current()['path'];
                     $nextTitle = $pagesIterator->current()['title'];
                  }
               }
               $pagesIterator->seek($currentPos);
            }
            // template variables
            $pageExtra = array(
               'nav'      => (isset($menuNav) ? $menuNav : ''),
               'previous' => (isset($previous) ? array('path' => $previous, 'title' => $prevTitle) : ''),
               'next'     => (isset($next) ? array('path' => $next, 'title' => $nextTitle) : ''),
            );
            $tplVariables = array(
               'octofoo' => array(
                  'version' => OctoFoo::VERSION,
                  'url'     => OctoFoo::URL
               ),
               'site' => new Proxy($this),
               'asset_path' => $this->getConfig()['site']['base_url'] . $this->getConfig()['site']['base_path'] . '/assets',
               'page' => array_merge($page, $pageExtra),
               'pages' => $pages
            );
            // rendering
            $rendered = $tplEngine->render($page['template'], $tplVariables);
            // dir/file writing
            //$source_path = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME . '/' . $page['path'];
            $source_path = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . $page['path'];
            $dest_path = $this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . $page['path'];
            $dest_file = $this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'];
            $sitemap_file_path = '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'];

            //echo "$source_path -> $dest_file\n";

            /*
            if (is_dir($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . $page['path'])) {
               Utils\RecursiveRmdir($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . $page['path']);
            }

            if (!@mkdir($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . $page['path'], 0777, true)) {
               throw new \Exception(sprintf('Cannot create %s', $this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . $page['path']));
            }

            Utils\RecursiveCopy($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/' . $this->getConfig()['site']['layout'] . '/' . self::ASSETS_DIRNAME,
               $this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . self::ASSETS_DIRNAME);
             */

            if (!is_dir($dest_path)) {
               if (!@mkdir($dest_path, 0777, true)) {
                  throw new \Exception(sprintf('Cannot create %s', $dest_path));
               }
            }

            if (is_file($dest_file)) {
               if (!@unlink($dest_file)) {
                  throw new \Exception(sprintf('Cannot delete %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
               }
               $this->addMessage('Deleting ' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename']);
            }
            if (!@file_put_contents(sprintf('%s%s', $this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), $rendered)) {
               throw new \Exception(sprintf('Cannot write to %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
            }
            $this->addMessage(sprintf("Writing %s%s", ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));

            // event postloop
            $this->triggerEvent(__FUNCTION__, array(
               'page' => $page
            ), 'postloop');

            //echo $pagesIterator->current()['fulldate'] . "\n";
            //echo $pagesIterator->current()['postdate'] . "\n";
            // Add current file to sitemap
            $this->_sitemap->addItem($sitemap_file_path, '1.0', 'weekly', $pagesIterator->current()['postdate']);

            // use by the next iteration
            $prevPos = $pagesIterator->key();
            $currentPos++;
            $pagesIterator->next();
         }


         // Write the sitemap Index
         $this->_sitemap->createSitemapIndex('http://hackernode.com:8000/', 'Today');
         $this->addMessage('Creating Sitemap');

         // Write the robots.txt
         $this->createRobotsTxt();
         $this->addMessage("Creating robots.txt");

         // Copy assets
         if (is_dir($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . self::ASSETS_DIRNAME)) {
            Utils\RecursiveRmdir($this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . self::ASSETS_DIRNAME);
         }
         //Utils\RecursiveCopy($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::ASSETS_DIRNAME, $this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
         Utils\RecursiveCopy($this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/' . $this->getConfig()['site']['layout'] . '/' . self::ASSETS_DIRNAME,
            $this->getWebsitePath() . '/' . self::OUTPUT_DIRNAME . '/' . self::ASSETS_DIRNAME);
         // Done!
         $this->addMessage('Copy assets directory (and sub)');
         //$this->addMessage($this->createReadmeFile());
         return $this->getMessages();
      }

      /**
       * Temporary method to wrap Twig (and more?) engine
       * @param  string $templatesPath Absolute path to templates files
       * @return object Twig
       */
      public function tplEngine($templatesPath)
      {
         $twigLoader = new \Twig_Loader_Filesystem($templatesPath);
         $twig = new \Twig_Environment($twigLoader, array(
            'autoescape' => false,
            'debug'      => true
         ));
         $twig->addExtension(new \Twig_Extension_Debug());
         return $twig;
      }

      /**
       * Return pages list
       * @param  string $subDir
       * @return array (path, url and title)
       */
        /*public function getPagesPath($subDir='')
        {
            $pagesPath = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
            $pagesPath = (
                !empty($subDir)
                ? $pagesPath . '/' . $subDir
                : $pagesPath
            );
            if (!is_dir($pagesPath)) {
                throw new \Exception(sprintf("Invalid %s/%s%s directory", self::CONTENT_DIRNAME, self::CONTENT_PAGES_DIRNAME, (!empty($subDir) ? '/' . $subDir : '')));
            }
            $pagesIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $pagesPath,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($pagesIterator as $page) {
                if ($page->isDir()) {
                    if (file_exists($page->getPathname() . '/index.md')) {
                        $pages[] = array(
                            'path'  => (!empty($subDir) ? $subDir . '/' : '')  . $pagesIterator->getSubPathName(),
                            'title' => (new FileInfo($page->getPathname() . '/index.md'))
                                ->parse($this->getConfig())->getData('info')['title'],
                        );
                    }
                }
            }
            return $pages;
        }*/

      /**
       * Return a console displayable tree of pages
       * @return iterator
       */
      public function getPagesTree()
      {
         //$pagesPath = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
         $pagesPath = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::CONTENT_DIRNAME;
         if (!is_dir($pagesPath)) {
            //throw new \Exception(sprintf("Invalid %s/%s directory", self::CONTENT_DIRNAME, self::CONTENT_PAGES_DIRNAME));
            throw new \Exception(sprintf("Invalid %s directory", self::CONTENT_DIRNAME));
         }
         $dirIterator = new \RecursiveDirectoryIterator($pagesPath, \RecursiveDirectoryIterator::SKIP_DOTS);
         $pages = new Utils\FilenameRecursiveTreeIterator(
            $dirIterator,
            Utils\FilenameRecursiveTreeIterator::SELF_FIRST
         );
         return $pages;
      }

      /**
       * Loads plugins in the plugins/ directory if exist
       * @return void
       */
      private function loadPlugins()
      {
         try {
            $configPlugins = $this->getConfig('plugins');
         } catch (\Exception $e) {
            $configPlugins = array();
         }
         $pluginsDirCore = __DIR__ . '/' . self::PLUGINS_DIRNAME;
         $pluginsDir     = $this->getWebsitePath() . '/' . self::OCTOFOO_DIRNAME . '/' . self::PLUGINS_DIRNAME;
         $pluginsIterator = new \AppendIterator();
         if (is_dir($pluginsDirCore)) {
            $pluginsIterator1 = new \FilesystemIterator($pluginsDirCore);
            $pluginsIterator->append($pluginsIterator1);
         }
         if (is_dir($pluginsDir)) {
            $pluginsIterator2 = new \FilesystemIterator($pluginsDir);
            $pluginsIterator->append($pluginsIterator2);
         }
         if (iterator_count($pluginsIterator) > 0) {
            foreach ($pluginsIterator as $plugin) {
               if (array_key_exists($plugin->getBasename(), $configPlugins)
                  && $configPlugins[$plugin->getBasename()] == 'disabled') {
                  continue;
               }
               if ($plugin->isDir()) {
                  $pluginName = $plugin->getBasename();
                  $pluginClass = "OctoFoo\\$pluginName";
                  include($plugin->getPathname() . "/Plugin.php");
                  if (class_exists($pluginClass)) {
                     $pluginObject = new $pluginClass($this->getEvents());
                     // init
                     if (method_exists($pluginObject, 'preInit')) {
                        $this->getEvents()->attach('init.pre', array($pluginObject, 'preInit'));
                     }
                     if (method_exists($pluginObject, 'postInit')) {
                        $this->getEvents()->attach('init.post', array($pluginObject, 'postInit'));
                     }
                     // loadpages
                     if (method_exists($pluginObject, 'postloopLoadPages')) {
                        $this->getEvents()->attach('loadPages.postloop', array($pluginObject, 'postloopLoadPages'));
                     }
                     // generate
                     if (method_exists($pluginObject, 'postloopGenerate')) {
                        $this->getEvents()->attach('generate.postloop', array($pluginObject, 'postloopGenerate'));
                     }
                  }
               }
            }
         }
      }
   }

   /**
    * Proxy class used by the template engine
    * "site.data" = "class.method"
    */
   class Proxy
   {
      protected $_octofoo;

      public function __construct($octofoo)
      {
         if (!$octofoo instanceof OctoFoo) {
            throw new \Exception('Proxy should be loaded with a OctoFoo instance');
         }
         $this->_octofoo = $octofoo;
      }

      /**
       * Magic method can get call like $site->name(), etc.
       * @todo do it better! :-)
       * @param  string $function
       * @param  array $arguments
       * @return string
       */
      public function __call($function, $arguments)
      {
            /*
            if (!method_exists($this->_octofoo, $function)) {
                throw new Exception(sprintf('Proxy erreor: Cannot get %s', $function));
            }
            return call_user_func_array(array($this->_octofoo, $function), $arguments);
             */
         $config = $this->_octofoo->getConfig();
         if (array_key_exists($function, $config['site'])) {
            if ($this->_octofoo->localServe === true) {
               $configToMerge['site']['base_url'] = 'http://localhost:8000';
               $config = array_replace_recursive($config, $configToMerge);
            }
            return $config['site'][$function];
         }
         if ($function == 'author') {
            return $config['author'];
         }
         if ($function == 'source') {
            return $config['deploy'];
         }
         return null;
      }

      public function getPages($subDir='')
      {
         return $this->_octofoo->getPages($subDir);
      }
   }

   /**
    * OctoFoo FileInfo, extended from SplFileInfo
    */
   class FileInfo extends \SplFileInfo
   {
      protected $_data = array();

      public function setData($key, $value)
      {
         $this->_data[$key] = $value;
         return $this;
      }

      public function getData($key='')
      {
         if ($key == '') {
            return $this->_data;
         }
         if (isset($this->_data[$key])) {
            return $this->_data[$key];
         }
      }

      public function getContents()
      {
         $level = error_reporting(0);
         $content = file_get_contents($this->getRealpath());
         error_reporting($level);
         if (false === $content) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
         }
         return $content;
      }

      public function parse()
      {
         if (!$this->isReadable()) {
            throw new \Exception('Cannot read file');
         }
         // parse front matter
         preg_match('/^<!--(.+)-->(.+)/s', $this->getContents(), $matches);
         // if not front matter, return content only
         if (!$matches) {
            $this->setData('content_raw', $this->getContents());
            return $this;
         }
         // $rawInfo    = front matter data
         // $rawContent = content data
         list($matchesAll, $rawInfo, $rawContent) = $matches;
         //echo $this->getRealPath() . "\n";
         //echo $rawInfo . "\n";
         // parse front matter
         $info = parse_ini_string($rawInfo);
         //print_r($info);
         $this->setData('info', $info);
         $this->setData('content_raw', $rawContent);
         return $this;
      }
   }

   /**
    * OctoFoo File iterator
    */
   class FileIterator extends \FilterIterator
   {
      protected $_extFilter = null;

      public function __construct($dirOrIterator = '.', $extFilter='')
      {
         if (is_string($dirOrIterator)) {
            if (!is_dir($dirOrIterator)) {
               throw new \InvalidArgumentException('Expected a valid directory name');
            }
            $dirOrIterator = new \RecursiveDirectoryIterator(
               $dirOrIterator,
               \FilesystemIterator::UNIX_PATHS
               |\RecursiveIteratorIterator::SELF_FIRST
            );
         }
         elseif (!$dirOrIterator instanceof \DirectoryIterator) {
            throw new \InvalidArgumentException('Expected a DirectoryIterator');
         }
         if ($dirOrIterator instanceof \RecursiveIterator) {
            $dirOrIterator = new \RecursiveIteratorIterator($dirOrIterator);
         }
         if (!empty($extFilter)) {
            $this->_extFilter = $extFilter;
         }
         parent::__construct($dirOrIterator);
         $this->setInfoClass('OctoFoo\FileInfo');
      }

      public function accept()
      {
         $file = $this->getInnerIterator()->current();
         if (!$file instanceof FileInfo) {
            return false;
         }
         if (!$file->isFile()) {
            return false;
         }
         if (!is_null($this->_extFilter)) {
            if ($file->getExtension() != $this->_extFilter) {
               return false;
            }
            return true;
         }
         return true;
      }
   }

   /**
    * OctoFoo console helper
    */
   class Console
   {
      protected $_console;

      public function __construct($console)
      {
            /*
            if (!($console instanceof Zend\Console\Adapter\AdapterInterface)) {
                throw new \Exception("Error");
            }
             */
         $this->_console = $console;
      }

      public function wlInfo($text)
      {
         echo '[' , $this->_console->write('INFO', Color::YELLOW) , ']' . "\t";
         $this->_console->writeLine($text);
      }
      public function wlDone($text)
      {
         echo '[' , $this->_console->write('DONE', Color::GREEN) , ']' . "\t";
         $this->_console->writeLine($text);
      }
      public function wlError($text)
      {
         echo '[' , $this->_console->write('ERROR', Color::RED) , ']' . "\t";
         $this->_console->writeLine($text);
      }
   }

   /**
    * OctoFoo plugin abstract
    */
   abstract class Plugin
   {
      const DEBUG = false;

      public function __call($name, $args)
      {
         if (self::DEBUG) {
            printf("[EVENT] %s is not implemented in %s plugin\n", $name, get_class(__FUNCTION__));
         }
      }

      public function trace($enabled=self::DEBUG, $e)
      {
         if ($enabled === true) {
            printf(
               '[EVENT] %s\%s %s' . "\n",
               get_class($this),
               $e->getName(),
               json_encode($e->getParams())
            );
         }
      }
   }


   /**
    * Sitemap
    *
    * This class used for generating Google Sitemap files
    *
    * @package    Sitemap
    * @author     Osman Üngür <osmanungur@gmail.com>
    * @copyright  2009-2015 Osman Üngür
    * @license    http://opensource.org/licenses/MIT MIT License
    * @link       http://github.com/o/sitemap-php
    */
   class Sitemap {

      /**
       *
       * @var XMLWriter
       */
      private $writer;
      private $domain;
      private $path;
      private $filename = 'sitemap';
      private $current_item = 0;
      private $current_sitemap = 0;

      const EXT = '.xml';
      const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';
      const DEFAULT_PRIORITY = 0.5;
      const ITEM_PER_SITEMAP = 50000;
      const SEPERATOR = '-';
      const INDEX_SUFFIX = 'index';

      /**
       *
       * @param string $domain
       */
      public function __construct($domain) {
         $this->setDomain($domain);
      }

      /**
       * Sets root path of the website, starting with http:// or https://
       *
       * @param string $domain
       */
      public function setDomain($domain) {
         $this->domain = $domain;
         return $this;
      }

      /**
       * Returns root path of the website
       *
       * @return string
       */
      private function getDomain() {
         return $this->domain;
      }

      /**
       * Returns XMLWriter object instance
       *
       * @return XMLWriter
       */
      private function getWriter() {
         return $this->writer;
      }

      /**
       * Assigns XMLWriter object instance
       *
       * @param XMLWriter $writer
       */
      private function setWriter(\XMLWriter $writer) {
         $this->writer = $writer;
      }

      /**
       * Returns path of sitemaps
       *
       * @return string
       */
      private function getPath() {
         return $this->path;
      }

      /**
       * Sets paths of sitemaps
       *
       * @param string $path
       * @return Sitemap
       */
      public function setPath($path) {
         $this->path = $path;
         return $this;
      }

      /**
       * Returns filename of sitemap file
       *
       * @return string
       */
      private function getFilename() {
         return $this->filename;
      }

      /**
       * Sets filename of sitemap file
       *
       * @param string $filename
       * @return Sitemap
       */
      public function setFilename($filename) {
         $this->filename = $filename;
         return $this;
      }

      /**
       * Returns current item count
       *
       * @return int
       */
      private function getCurrentItem() {
         return $this->current_item;
      }

      /**
       * Increases item counter
       *
       */
      private function incCurrentItem() {
         $this->current_item = $this->current_item + 1;
      }

      /**
       * Returns current sitemap file count
       *
       * @return int
       */
      private function getCurrentSitemap() {
         return $this->current_sitemap;
      }

      /**
       * Increases sitemap file count
       *
       */
      private function incCurrentSitemap() {
         $this->current_sitemap = $this->current_sitemap + 1;
      }

      /**
       * Prepares sitemap XML document
       *
       */
      private function startSitemap() {
         $this->setWriter(new \XMLWriter());
         if ($this->getCurrentSitemap()) {
            $this->getWriter()->openURI($this->getPath() . $this->getFilename() . self::SEPERATOR . $this->getCurrentSitemap() . self::EXT);
         } else {
            $this->getWriter()->openURI($this->getPath() . $this->getFilename() . self::EXT);
         }
         $this->getWriter()->startDocument('1.0', 'UTF-8');
         $this->getWriter()->setIndent(true);
         $this->getWriter()->startElement('urlset');
         $this->getWriter()->writeAttribute('xmlns', self::SCHEMA);
      }

      /**
       * Adds an item to sitemap
       *
       * @param string $loc URL of the page. This value must be less than 2,048 characters.
       * @param string $priority The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
       * @param string $changefreq How frequently the page is likely to change. Valid values are always, hourly, daily, weekly, monthly, yearly and never.
       * @param string|int $lastmod The date of last modification of url. Unix timestamp or any English textual datetime description.
       * @return Sitemap
       */
      public function addItem($loc, $priority = self::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL) {
         if (($this->getCurrentItem() % self::ITEM_PER_SITEMAP) == 0) {
            if ($this->getWriter() instanceof \XMLWriter) {
               $this->endSitemap();
            }
            $this->startSitemap();
            $this->incCurrentSitemap();
         }
         $this->incCurrentItem();
         $this->getWriter()->startElement('url');
         $this->getWriter()->writeElement('loc', $this->getDomain() . $loc);
         $this->getWriter()->writeElement('priority', $priority);
         if ($changefreq)
            $this->getWriter()->writeElement('changefreq', $changefreq);
         if ($lastmod)
            $this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
         $this->getWriter()->endElement();
         return $this;
      }

      /**
       * Prepares given date for sitemap
       *
       * @param string $date Unix timestamp or any English textual datetime description
       * @return string Year-Month-Day formatted date.
       */
      private function getLastModifiedDate($date) {
         if (ctype_digit($date)) {
            return date('Y-m-d', $date);
         } else {
            $date = strtotime($date);
            return date('Y-m-d', $date);
         }
      }

      /**
       * Finalizes tags of sitemap XML document.
       *
       */
      private function endSitemap() {
         if (!$this->getWriter()) {
            $this->startSitemap();
         }
         $this->getWriter()->endElement();
         $this->getWriter()->endDocument();
      }

      /**
       * Writes Google sitemap index for generated sitemap files
       *
       * @param string $loc Accessible URL path of sitemaps
       * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
       */
      public function createSitemapIndex($loc, $lastmod = 'Today') {
         $this->endSitemap();
         $indexwriter = new \XMLWriter();
         $indexwriter->openURI($this->getPath() . $this->getFilename() . self::SEPERATOR . self::INDEX_SUFFIX . self::EXT);
         $indexwriter->startDocument('1.0', 'UTF-8');
         $indexwriter->setIndent(true);
         $indexwriter->startElement('sitemapindex');
         $indexwriter->writeAttribute('xmlns', self::SCHEMA);
         for ($index = 0; $index < $this->getCurrentSitemap(); $index++) {
            $indexwriter->startElement('sitemap');
            $indexwriter->writeElement('loc', $loc . $this->getFilename() . ($index ? self::SEPERATOR . $index : '') . self::EXT);
            $indexwriter->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
            $indexwriter->endElement();
         }
         $indexwriter->endElement();
         $indexwriter->endDocument();
      }

   }

}

/**
 * Utils
 */
namespace OctoFoo\Utils
{
   /**
    * Recursively remove a directory
    *
    * @param string $dirname
    * @param boolean $followSymlinks
    * @return boolean
    */
   function RecursiveRmdir($dirname, $followSymlinks=false) {
      if (is_dir($dirname) && !is_link($dirname)) {
         if (!is_writable($dirname)) {
            throw new \Exception(sprintf('%s is not writable!', $dirname));
         }
         $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirname),
            \RecursiveIteratorIterator::CHILD_FIRST
         );
         while ($iterator->valid()) {
            if (!$iterator->isDot()) {
               if (!$iterator->isWritable()) {
                  throw new \Exception(sprintf(
                     '%s is not writable!',
                     $iterator->getPathName()
                  ));
               }
               if ($iterator->isLink() && $followLinks === false) {
                  $iterator->next();
               }
               if ($iterator->isFile()) {
                  @unlink($iterator->getPathName());
               }
               elseif ($iterator->isDir()) {
                  @rmdir($iterator->getPathName());
               }
            }
            $iterator->next();
         }
         unset($iterator);

         return @rmdir($dirname);
      }
      else {
         throw new \Exception(sprintf('%s does not exist!', $dirname));
      }
   }

   /**
    * Copy a dir, and all its content from source to dest
    */
   function RecursiveCopy($source, $dest) {
      if (!is_dir($dest)) {
         @mkdir($dest);
      }
      $iterator = new \RecursiveIteratorIterator(
         new \RecursiveDirectoryIterator(
            $source,
            \RecursiveDirectoryIterator::SKIP_DOTS
         ),
         \RecursiveIteratorIterator::SELF_FIRST
      );
      foreach ($iterator as $item) {
         if ($item->isDir()) {
            //echo "MKDIR: " . $dest . DS . $iterator->getSubPathName() . "\n";
            @mkdir($dest . DS . $iterator->getSubPathName());
         }
         else {
            //echo "COPY $item\n";
            @copy($item, $dest . DS . $iterator->getSubPathName());
         }
      }
   }

   /**
    * Execute git commands
    *
    * @param string working directory
    * @param array git commands
    * @return void
    */
   function runGitCmd($wd, $commands)
   {
      $cwd = getcwd();
      chdir($wd);
      exec('git config core.autocrlf false');
      foreach ($commands as $cmd) {
         printf("> git %s\n", $cmd);
         exec(sprintf('git %s', $cmd));
      }
      chdir($cwd);
   }

   /**
    * Replace Filepath by Filename
    */
   class FilenameRecursiveTreeIterator extends \RecursiveTreeIterator
   {
      public function current()
      {
         return str_replace(
            $this->getInnerIterator()->current(),
            substr(strrchr($this->getInnerIterator()->current(), DIRECTORY_SEPARATOR), 1),
            parent::current()
         );
      }
   }

   function isWindows()
   {
      return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
   }

   function slugify($string) {

      return md5($string);

      $separator = '-';
      $string = preg_replace('/
         [\x09\x0A\x0D\x20-\x7E]              # ASCII
         | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
         |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
         | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
         |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
         |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
         | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
         |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
         /', '', $string);
      // @see https://github.com/cocur/slugify/blob/master/src/Cocur/Slugify/Slugify.php

      // transliterate
      $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
      // replace non letter or digits by seperator
      $string = preg_replace('#[^\\pL\d]+#u', $separator, $string);
      // trim
      $string = trim($string, $separator);
      // lowercase
      $string = (defined('MB_CASE_LOWER')) ? mb_strtolower($string) : strtolower($string);
      // remove unwanted characters
      $string = preg_replace('#[^-\w]+#', '', $string);

      return $string;
   }
}
