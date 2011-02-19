<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Command_Download extends Swoosh_Base_Command_Abstract {
  const WP_PLUGIN_REPO_URL = 'http://wordpress.org/extend/plugins/%s/download/';
  const WP_THEME_REPO_URL = 'http://themes.svn.wordpress.org/%s/';
  const WP_THEME_DOWNLOAD_URL = 'http://wordpress.org/extend/themes/download/%s.%s.zip';

  /**
   * Store the Zend Http client instance
   * @var Zend_Http_Client
   */
  protected $httpClient;
  /**
   * Contains the filename of the currently downloaded project
   * @var string
   */
  private $currentDownload;

  public function __construct() {
    $this->httpClient = new Zend_Http_Client();
    $this->httpClient->setConfig(array(
        'maxredirects' => 0,
        'timeout' => 15,
    ));
    $this->httpClient->setHeaders(array(
        'User-Agent: WP Swoosh v 0.1',
    ));
  }

  public function downloadAction($eventName, $extraArguments) {
    try {
      /**
       * If we specify a second argument for download, then we are probably downloading a plugin
       */
      if (isset($extraArguments[1])) {
        Swoosh_Print::in('Discovering project information for', $extraArguments[1] . '...');
        /**
         * We need to try twice to locate the project, because it might be a plugin or a theme
         */
        $attempt = 1;
        $match = false;
        while ($attempt < 3) {
          $match = $this->discoverProject($extraArguments[1], $attempt);
          if ($match) {
            break;
          }
          $attempt++;
        }
        if (!$match) {
          throw new Zend_Exception('Cannot find project');
        }
      } else {
        $this->discoverCore();
      }
    } catch (Zend_Exception $e) {
      Swoosh_Print::ln($e->getMessage());
    }

    return $this;
  }

  public function helpAction($eventName, $extraArguments) {
    Swoosh_Print::ln("\t", 'download: download a project');
    return $this;
  }

  public function getRules() {
    return array(
        'beta' => 'Whether the package is beta or not',
        'version=s' => 'Set a specific version to download',
    );
  }

  /**
   * Discover the project (whether it's a plugin or theme, and specific version)
   * @param string $project
   * @param int $attempt
   * @return Command_Download
   */
  protected function discoverProject($project, $attempt = 1) {
    switch ($attempt) {
      case 1:
        $this->httpClient->setUri(sprintf(self::WP_PLUGIN_REPO_URL, $project));
        /**
         * If we specify specific version, look for that
         */
        if (Swoosh_Core::getInstance()->getConsole()->getOption('version')) {
          $regex = sprintf('!\'(http://downloads.wordpress.org/plugin(%s))\'!', '.*' . Swoosh_Core::getInstance()->getConsole()->getOption('version') . '.zip');
        } else {
          $regex = sprintf('!\'(http://downloads.wordpress.org/plugin(%s))\'!', '.*.zip');
        }
        break;
      case 2:
        $this->httpClient->setUri(sprintf(self::WP_THEME_REPO_URL, $project));

        if (Swoosh_Core::getInstance()->getConsole()->getOption('version')) {
          $regex = sprintf('!\"(%s)/\"!', $version);
        } else {
          $regex = sprintf('!\"%s/\"!', "(\d(.*))*");
        }

        break;
    }
    /**
     *  @var Zend_Http_Reponse
     */
    $response = $this->httpClient->request(Zend_Http_Client::GET);
    /**
     * Looks like wordpress.org returns 302 when a project is not found
     */
    try {
      if ($response->getStatus() == '200') {
        preg_match_all($regex, $response->getBody(), $matches);
        if (count($matches[1]) > 0) {
          if (1 == $attempt) {
            $projectUrl = array_shift($matches[1]);
          } elseif (2 == $attempt) {
            $projectUrl = sprintf(self::WP_THEME_DOWNLOAD_URL, $project, array_pop($matches[1]));
          }
          Swoosh_Print::ln(' [OK]');
          $this->download($projectUrl)->unzip();
          return true;
        }
      }
    } catch (Zend_Exception $e) {
      Swoosh_Print::ln($e->getMessage());
    }

    return false;
  }

  /**
   * Download a file from the specified url
   * @param string $url
   * @param string $savePath
   * @return Command_Download
   */
  protected function download($url, $savePath = null) {
    try {
      Swoosh_Print::in('Downloading project...');
      $this->httpClient->setUri($url);
      $response = $this->httpClient->request(Zend_Http_Client::GET);

      if ($response->getStatus() == '200') {
        $this->currentDownload = array_pop(explode('/', $url));
        file_put_contents($this->currentDownload, $response->getBody());
      } else {
        throw new Zend_Exception(sprintf('Error downloading project file %s', $projectUrl));
      }
    } catch (Zend_Exception $e) {
      Swoosh_Print::ln($e->getMessage());
    }

    return $this;
  }

  /**
   * Unzip the downloaded archive
   * @param string $destination
   * @return Command_Download
   */
  protected function unzip($destination = '.') {
    $zip = new ZipArchive();
    try {
      if ($zip->open($this->currentDownload)) {
        $zip->extractTo($destination);
        $zip->close();
        Swoosh_Print::ln(' [OK]');
        unlink($this->currentDownload);
        $this->currentDownload = null;
      }
    } catch (Exception $e) {
      Swoosh_Print::ln($e->getMessage());
    }

    return $this;
  }

}