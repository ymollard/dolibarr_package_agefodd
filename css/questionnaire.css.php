<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FI8TNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/eldy/style.css.php
 *		\brief      File for CSS style sheet Eldy
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');



session_cache_limiter('public');


$res = @include ("../../main.inc.php"); // For root directory
if (! $res){ $res = @include ("../../../main.inc.php"); }// For "custom" directory
if (! $res){ die("Include of main fails"); }


// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

$nbcols = 12;

?>


/* ==== GRID SYSTEM ==== */

.agf_row {
    position: relative;
    width: 100%;
}

.agf_row [class^="agf_col"]{
    float: left;
    padding: 0.5rem 0% 0.5rem 2%;
    min-height: 0.125rem;
}
.agf_row [class^="agf_col"]:first-child{
    padding: 0.5rem 0% 0.5rem 0%;
}

<?php

$Tclass=array();
for ($i = 1; $i <= 12; $i++)  {
    $Tclass[] = '.agf_col-'.$i;
}

print implode(",\n",$Tclass) ?>
{
    width: 100%;
    box-sizing: border-box;
}

<?php
// generate agf_col-X-sm
for ($i = 1; $i <= 12; $i++)  {
    print '.agf_col-'.$i.'-sm {';
    $colWidth = 100/$nbcols * $i;
    print 'width: '.(round($colWidth,2)).'%;';
    print "}\n";
}
?>

.agf_row::after {
    content: "";
    display: table;
    clear: both;
}

.agf_hidden-sm {
    display: none;
}



@media only screen and (min-width: 45em) {  /* 720px */
<?php
// generate agf_col-X-sm
for ($i = 1; $i <= 12; $i++)  {
    print '    .agf_col-'.$i.' {';
    $colWidth = 100/$nbcols * $i;
    print 'width: '.(round($colWidth,2)).'%;';
    print "}\n";
}
?>

    .agf_hidden-sm {
        display: block;
    }
}



.agf_left_nav{
    border-right: 1px solid #ededed;
}

.agf_title_head{
    border-bottom:  1px solid #ededed;
}

a.agf_btn {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-image: none;
    border: 1px solid transparent;
    border-radius: 3px;
    box-shadow: none;
    text-decoration: none;
}

a.agf_btn-app {
    border-radius: 3px;
    position: relative;
    padding: 15px 5px;
    margin: 0 0 10px 10px;
    min-width: 80px;
    /*height: 60px;*/
    text-align: center;
    color: #666;
    border: none;
    background: none;
    font-size: 12px;
    font-weight: 300;
}

a.agf_btn-app:hover {
    border-radius: 3px;
    position: relative;
    padding: 15px 5px;
    margin: 0 0 10px 10px;
    min-width: 80px;
    /*height: 60px;*/
    text-align: center;
    color: #1c77b1;
    background-color: #f4f4f4;
    font-size: 12px;
}

.agf_btn-app>.fa {
    font-size: 20px;
    display: block;
}



.agf_nav {
    padding-left: 0;
    margin-bottom: 0;
    list-style: none;
}
.agf_nav>li>a {
    position: relative;
    display: block;
    padding: 10px 15px;
}

.agf_nav-stacked>li {
    border-bottom: 1px solid #e6e6e6;
    margin: 0;
}
.agf_nav-pills>li {
    float: left;
}
.agf_nav-stacked>li {
    float: none;
}

.agf_nav>li {
    position: relative;
    display: block;
}


.agf_nav-pills>li.active>a, .agf_nav-pills>li.active>a:hover, .agf_nav-pills>li.active>a:focus {
    border-top-color: #3c8dbc;
}

.agf_nav-stacked>li>a:hover {
    background: #ededed;
    color: #444;
    border-top: 0;
    border-right-color: #3c8dbc;
    text-decoration: none;
}

.agf_nav-pills>li.active>a, .agf_nav-pills>li.active>a:focus, .agf_nav-pills>li.active>a:hover {
    color: #fff;
    background-color: #337ab7;
}


.agf_nav-pills>li.active>a {
    font-weight: 600;
}


.agf_nav-stacked>li>a {
    border-radius: 0;
    border-top: 0;
    border-right: 3px solid transparent;
    color: #444;
}
