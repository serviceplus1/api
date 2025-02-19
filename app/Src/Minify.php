<?php

use \MatthiasMullie\Minify;

$sourcePath = dirname(__DIR__, 2)."/public/assets";

/* CSS Util */
$utilCSS = new Minify\CSS();
$utilCSS->add($sourcePath."/_css/sb-admin-2.min.css"); /* SB Admin 2 */
$utilCSS->add($sourcePath."/dist/toggle/css/bootstrap4-toggle.min.css"); /* Bootstrap Toggle */

$utilCSS->minify($sourcePath."/_css/util.min.css");

/* JS util */
$utilJS = new Minify\JS();
$utilJS->add($sourcePath."/dist/jquery/jquery.min.js"); /* Jquery */
$utilJS->add($sourcePath."/dist/bootstrap/js/bootstrap.bundle.min.js"); /* Bootstrap bundle */
$utilJS->add($sourcePath."/dist/jquery-easing/jquery.easing.min.js"); /* jQuery Easing */
$utilJS->add($sourcePath."/dist/toggle/js/bootstrap4-toggle.min.js"); /* Bootstrap toggle */

$utilJS->minify($sourcePath."/_js/util.min.js");

/* Main JS */
$mainJS  = new Minify\JS();
$mainJS->add($sourcePath."/scripts/sb-admin-2.min.js"); /* SB Admin 2 */
$mainJS->add($sourcePath."/scripts/form.js"); /* JS forms */
$mainJS->add($sourcePath."/scripts/base.js"); /* JS base */

$mainJS->minify($sourcePath."/_js/main.min.js");

/* Validate */
$validateJS = new Minify\JS();
$validateJS->add($sourcePath."/dist/validate/jquery.validate.js");
$validateJS->add($sourcePath."/dist/validate/jquery.validate.methods.js");
$validateJS->minify($sourcePath."/dist/validate/jquery.validate.all.min.js");

/* Select */
// $validateJS = new Minify\JS();
// $validateJS->add($sourcePath."/dist/validate/jquery.validate.js");
// $validateJS->add($sourcePath."/dist/validate/jquery.validate.methods.js");
// $validateJS->minify($sourcePath."/dist/validate/jquery.validate.all.min.js");

/* Login JS */
// $loginJS  = new Minify\JS();
// $loginJS->add($sourcePath."/scripts/login.js");
// $loginJS->minify($sourcePath."/_js/login.min.js");
