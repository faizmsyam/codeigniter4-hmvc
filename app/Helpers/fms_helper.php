<?php

if (!function_exists('minifier')) {
	function minifier($code)
	{
		// Protect </script> inside JS string literals from being mangled by the
		// HTML-minification pass. This is safe to apply broadly because:
		//   - Actual HTML closing tags are still processed by the later unescape.
		//   - The minifier never needs to collapse whitespace around </script>.
		$code = str_replace('<\/script>', "\x00SCRIPT_ESC\x00", $code);

		$search = array(
			// Remove whitespaces after tags
			'/\>[^\S ]+/s',
			// Remove whitespaces before tags
			'/[^\S ]+\</s',
			// Remove multiple whitespace sequences
			'/(\s)+/s',
			// Removes HTML comments
			'/<!--(.|\s)*?-->/'
		);
		$replace = array('>', '<', '$1', '');
		$code = preg_replace($search, $replace, $code);

		// Restore escaped script closes
		$code = str_replace("\x00SCRIPT_ESC\x00", '<\/script>', $code);

		return $code;
	}
}

if (!function_exists('fmsAssets')) {
  function fmsAssets(string $dir, string $filename)
  {
    $path = FCPATH . "assets/fms/{$dir}/{$filename}";
    if ($filename && file_exists($path)) {
      return base_url("assets/fms/{$dir}/{$filename}");
    }
    return base_url('assets/fms/img/placeholder/transparent.png');
  }
}

if (!function_exists('fmsAssetUrl')) {
  function fmsAssetUrl(string $dir, ?string $filename): ?string
  {
    if (!$filename) {
      return null;
    }
    $path = WRITEPATH . "uploads/fms-drives/{$dir}/{$filename}";
    if (!is_file($path)) {
      return null;
    }
    return base_url("uploads/fms-drives/{$dir}/{$filename}");
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