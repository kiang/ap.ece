<?php
$rootPath = dirname(__DIR__);

$now = date('Y-m-d H:i:s');

exec("cd {$rootPath} && /usr/bin/git pull");

exec("/usr/bin/node {$rootPath}/app.js");

exec("php -q {$rootPath}/php/01_fetch.php");

exec("cd {$rootPath} && /usr/bin/git add -A");

exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");

exec("cd {$rootPath} && /usr/bin/git push origin master");