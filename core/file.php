<?php 

/**
 * File
 *
 * @package   Kirby CMS
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      http://getkirby.com
 * @copyright Bastian Allgeier
 * @license   http://getkirby.com/license
 */
abstract class FileAbstract extends Media {

  public $site;
  public $page;
  public $files;

  /**
   * Constructor
   * 
   * @param Files The parent files collection 
   * @param string The filename
   */
  public function __construct(Files $files, $filename) {

    $this->site  = $files->site();
    $this->page  = $files->page();
    $this->files = $files;
    $this->root  = $this->files->page()->root() . DS . $filename;

    parent::__construct($this->root);

  }

  /**
   * Returns the parent site object
   * 
   * @return Site
   */
  public function site() {
    return $this->site;
  }

  /**
   * Returns the parent page object
   * 
   * @return Page
   */
  public function page() {
    return $this->site;
  }

  /**
   * Returns the parent files collection
   *
   * @return Files
   */
  public function files() {
    return $this->files;
  }

  public function siblings() {
    return $this->files->not($this->filename);
  }

  function next() {
    $siblings = $this->files;
    $index    = $siblings->indexOf($this);
    if($index === false) return false;
    return $this->files->nth($index+1);
  }

  function hasNext() {
    return $this->next();     
  }
  
  function prev() {
    $siblings = $this->files;
    $index    = $siblings->indexOf($this);
    if($index === false) return false;
    return $this->files->nth($index-1);
  }

  function hasPrev() {
    return $this->prev();       
  }

  /**
   * Returns the absolute URL for the file
   * 
   * @return string
   */
  public function url() {
    return $this->site->options['url.content'] . '/' . $this->page->diruri() . '/' . $this->filename;
  }

  /**
   * Get the meta information 
   * 
   * @return Content
   */
  public function meta() {

    if(isset($this->cache['meta'])) {
      return $this->cache['meta'];
    } else {

      $inventory = $this->page->inventory();
      $file      = isset($inventory['meta'][$this->filename]) ? $this->page->root() . DS . $inventory['meta'][$this->filename] : null;

      return $this->cache['meta'] = new Content($this->page, $file);      

    }

  }

  /**
   * Magic getter for all meta fields
   * 
   * @return Field
   */
  public function __call($key, $arguments = null) {
    return $this->meta()->get($key, $arguments);
  }

  /**
   * Renames the file and also its meta info txt
   *  
   * @param string $filename
   */
  public function rename($name) {

    $filename = f::safeName($name) . '.' . $this->extension();
    $root     = $this->dir() . DS . $filename;

    if($root == $this->root()) return true;

    if(file_exists($root)) {
      throw new Exception('A file with that name already exists');
    }

    if(!f::move($this->root(), $root)) {
      throw new Exception('The file could not be renamed');
    }

    if($this->meta()->exists()) {
      f::move($this->meta()->root(), $root . '.' . c::get('content.file.extension'));
    }

    return true;

  }

  public function update($data = array()) {

    $data  = array_merge((array)$this->meta()->toArray(), $data);    
    $store = $this->root() . '.' . c::get('content.file.extension');      

    foreach($data as $k => $v) {
      if(is_null($v)) unset($data[$k]);
    }

    if(!data::write($store, $data, 'kd')) {
      throw new Exception('The file data could not be saved');
    }

    return true;

  }

  public function delete() {

    if($this->meta()->exists()) {
      if(!f::remove($this->meta()->root())) {
        throw new Exception('The meta file could not be deleted');
      }
    }

    if(!f::remove($this->root())) {
      throw new Exception('The file could not be deleted');
    }

    return true;

  }

  /**
   * Makes it possible to echo the entire object
   * 
   * @return string
   */
  public function __toString() {
    return $this->root;
  }

}