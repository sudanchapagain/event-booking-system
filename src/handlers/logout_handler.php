<?php

session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
