<?php

namespace ZfcTwig;

use Zend\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
    /**
     * @var string
     */
    protected $environmentLoader;

    /**
     * @var string
     */
    protected $environmentClass;

    /**
     * @var array
     */
    protected $environmentOptions = [];

    /**
     * @var array
     */
    protected $globals = [];

    /**
     * @var array
     */
    protected $loaderChain = [];

    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @var string
     */
    protected $suffix;

    /**
     * @var bool
     */
    protected $enableFallbackFunctions = true;

    /**
     * @var bool
     */
    protected $disableZfmodel = true;

    /**
     * @var array
     */
    protected $helperManager = [];

    /**
     * @param boolean $disableZfmodel
     * @return self
     */
    public function setDisableZfmodel($disableZfmodel)
    {
        $this->disableZfmodel = $disableZfmodel;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDisableZfmodel()
    {
        return $this->disableZfmodel;
    }

    /**
     * @param boolean $enableFallbackFunctions
     * @return self
     */
    public function setEnableFallbackFunctions($enableFallbackFunctions)
    {
        $this->enableFallbackFunctions = $enableFallbackFunctions;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnableFallbackFunctions()
    {
        return $this->enableFallbackFunctions;
    }

    /**
     * @param mixed $environmentLoader
     * @return self
     */
    public function setEnvironmentLoader($environmentLoader)
    {
        $this->environmentLoader = $environmentLoader;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEnvironmentLoader()
    {
        return $this->environmentLoader;
    }

    /**
     * @param array $environmentOptions
     * @return self
     */
    public function setEnvironmentOptions($environmentOptions)
    {
        $this->environmentOptions = $environmentOptions;
        return $this;
    }

    /**
     * @return array
     */
    public function getEnvironmentOptions()
    {
        return $this->environmentOptions;
    }

    /**
     * @param array $extensions
     * @return self
     */
    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param array $helperManager
     * @return self
     */
    public function setHelperManager($helperManager)
    {
        $this->helperManager = $helperManager;
        return $this;
    }

    /**
     * @return array
     */
    public function getHelperManager()
    {
        return $this->helperManager;
    }

    /**
     * @param array $loaderChain
     * @return self
     */
    public function setLoaderChain($loaderChain)
    {
        $this->loaderChain = $loaderChain;
        return $this;
    }

    /**
     * @return array
     */
    public function getLoaderChain()
    {
        return $this->loaderChain;
    }

    /**
     * @param string $suffix
     * @return self
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @param string $environmentClass
     */
    public function setEnvironmentClass($environmentClass)
    {
        $this->environmentClass = $environmentClass;
    }

    /**
     * @return string
     */
    public function getEnvironmentClass()
    {
        return $this->environmentClass;
    }

    /**
     * @param array $globals
     */
    public function setGlobals($globals)
    {
        $this->globals = $globals;
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return $this->globals;
    }
}

