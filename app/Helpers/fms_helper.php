<?php

if (!function_exists('minifier')) {
	function minifier($code)
	{
		$search = array(
      // Remove whitespaces after tags
      '/\>[^\S ]+/s',
      // Remove whitespaces before tags
      '/[^\S ]+\</s',
      // Remove multiple whitespace sequences
      '/(\s)+/s',
      // Removes comments
      '/<!--(.|\s)*?-->/'
    );
    $replace = array('>', '<', '\\1');
    $code = preg_replace($search, $replace, $code);
    return $code;
	}
}

if (!function_exists('assets')) {
  function assets(string $dir, string $filename)
  {
    $path = FCPATH . 'assets/fms' . '/' . $dir . '/' . $filename;
    if ($filename && file_exists($path)) {
      return base_url('assets/fms' . '/' . $dir . '/' . $filename);
    }
    return base_url('assets/fms/img/placeholder/transparent.png');
  }
}

function fmsSignature()
{
  $ref = isset($_GET['ref']) ? $_GET['ref'] : '';
  $lock_ref = APP_SIGNATURE . date('Y');
  $locked = false;
  if (($ref !== $lock_ref)) {
    $locked = true;
  }
  return $locked;
}