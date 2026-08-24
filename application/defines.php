<?php

/*
 *     ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ    
 *  ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ 
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆ   ÆÆÆ   ÆÆÆ   ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ                       
 * ÆÆÆÆÆÆÆÆÆ                        
 * ÆÆÆÆÆÆÆÆÆ                        
 * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 *  ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ 
 *     ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ   
 * 
 *             WebCycles
 * 
 * File Name: application/defines.php
 * Version: 1.0.0
 * Description: TODO
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

/** 
 * Application version
 *  
 * @var string WEBCYCLES_VERSION
 * @since 1.0.0
 */
const WEBCYCLES_VERSION = "1.0.0";

/** 
 * Application required PHP version
 *  
 * @var string WEBCYCLES_REQUIRED_PHP_VERSION
 * @since 1.0.0
 */
const WEBCYCLES_REQUIRED_PHP_VERSION = "8.4";

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

/** 
 * Application base path
 *  
 * @var string WEBCYCLES_PATH
 * @since 1.0.0
 */
define("WEBCYCLES_PATH", dirname(__DIR__));

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_APPLICATION_PATH
 * @since 1.0.0
 */
const WEBCYCLES_APPLICATION_PATH = WEBCYCLES_PATH . DIRECTORY_SEPARATOR . "application";

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_APPLICATION_PATH
 * @since 1.0.0
 */
const WEBCYCLES_APPLICATION_BUILTIN_PATH = WEBCYCLES_APPLICATION_PATH . DIRECTORY_SEPARATOR . "builtin";

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_COMPONENTS_PATH
 * @since 1.0.0
 */
const WEBCYCLES_COMPONENTS_PATH = WEBCYCLES_PATH . DIRECTORY_SEPARATOR . "components";

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_CONFIGURATIONS_PATH
 * @since 1.0.0
 */
const WEBCYCLES_CONFIGURATIONS_PATH = WEBCYCLES_PATH . DIRECTORY_SEPARATOR . "configurations";

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_PUBLIC_PATH
 * @since 1.0.0
 */
const WEBCYCLES_PUBLIC_PATH = WEBCYCLES_PATH . DIRECTORY_SEPARATOR . "public";

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_STORAGE_PATH
 * @since 1.0.0
 */
const WEBCYCLES_STORAGE_PATH = WEBCYCLES_PATH . DIRECTORY_SEPARATOR . "storage";

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_STORAGE_CACHE_PATH
 * @since 1.0.0
 */
const WEBCYCLES_STORAGE_CACHE_PATH = WEBCYCLES_STORAGE_PATH . DIRECTORY_SEPARATOR . "cache";

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_STORAGE_RUNTIME_PATH
 * @since 1.0.0
 */
const WEBCYCLES_STORAGE_RUNTIME_PATH = WEBCYCLES_STORAGE_PATH . DIRECTORY_SEPARATOR . "runtime";

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_STORAGE_TEMPORARY_PATH
 * @since 1.0.0
 */
const WEBCYCLES_STORAGE_TEMPORARY_PATH = WEBCYCLES_STORAGE_PATH . DIRECTORY_SEPARATOR . "temporary";

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

/**
 * DESCRIPTION
 * 
 * @var string WEBCYCLES_VENDOR_PATH
 * @since 1.0.0
 */
const WEBCYCLES_VENDOR_PATH = WEBCYCLES_PATH . DIRECTORY_SEPARATOR . "vendor";

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */