<?php

/**
 * Copyright (C) 2009-2011 Shadez <https://github.com/Shadez>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 **/

/**
 * This file contains core constants
 * Please, do not modify these names and/or values if you don't know what are you doing.
 **/

/** Locale defines **/
define('LOCALE_DE', 3);
define('LOCALE_EN', 0);
define('LOCALE_ES', 6);
define('LOCALE_FR', 2);
define('LOCALE_RU', 8);

define('LOCALE_SINGLE', 1);
define('LOCALE_DOUBLE', 2);
define('LOCALE_SPLIT',  3);
define('LOCALE_PATH',   4);

/** Account Manager **/
define('ACCMGR_IDLE', 0);
define('ACCMGR_PERFORM_LOGIN', 1);
define('ACCMGR_LOGGED_IN', 2);
define('ACCMGR_LOGGED_OFF', 3);
define('ACCMGR_SPECIAL_OPERATION', 4);

define('NORMALIZE_TO', 1);
define('NORMALIZE_FROM', 2);

define('ERROR_NONE', 0);
define('ERROR_EMPTY_USERNAME', 1);
define('ERROR_EMPTY_PASSWORD', 2);
define('ERROR_WRONG_USERNAME_OR_PASSWORD', 4);
define('ERORR_INVALID_PASSWORD_FORMAT', 8);
define('ERROR_USER_LOCKED', 16);
define('ERROR_USER_BANNED', 32);
define('ERORR_INVALID_PASSWORD_RECOVERY_COMBINATION', 64);
define('ERORR_INVALID_PASSWORD_RECOVERY_ANSWER', 128);
define('ERORR_NEW_PASSWORD_NOT_MATCH', 256);
define('ERORR_NEW_PASSWORD_FAIL', 512);
define('ERROR_USERNAME_TAKEN', 1024);

/** Time **/
define('IN_MILISECONDS', 1000);
define('IN_SECONDS', 1);
define('IN_MINUTES', 60 * IN_SECONDS);
define('IN_HOURS', 60 * IN_MINUTES);
define('IN_DAYS', 24 * IN_HOURS);
define('IN_WEEKS', 7 * IN_DAYS);
define('IN_MONTHS', 30 * IN_DAYS);