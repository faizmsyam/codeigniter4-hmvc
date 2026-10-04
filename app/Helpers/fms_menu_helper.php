<?php

function fmsBuildMenuTree(array $menus, $parentId = null)
{
  $branch = [];

  foreach ($menus as $menu) {
    if ($menu->id_parent == $parentId) {
      $children = fmsBuildMenuTree($menus, $menu->id);
      if (!empty($children)) {
        $menu->children = $children;
      }
      $branch[] = $menu;
    }
  }

  return $branch;
}

function fmsMenuIsActive($url, string $currentUri): bool
{
  if (!$url || $url === '#') {
    return false;
  }

  return trim($currentUri, '/') === trim($url, '/')
    || str_starts_with(trim($currentUri, '/'), trim($url, '/'));
}

function fmsMenuHasActiveChild($menu, string $currentUri): bool
{
  if (!isset($menu->children)) {
    return false;
  }

  foreach ($menu->children as $child) {
    if (fmsMenuIsActive($child->url, $currentUri) || fmsMenuHasActiveChild($child, $currentUri)) {
      return true;
    }
  }
  return false;
}

function fmsEncodeId(int $id): string
{
  return (new \App\Libraries\FMSIdObfuscator())->encode($id);
}

function fmsDecodeId(string $hash): ?int
{
  return (new \App\Libraries\FMSIdObfuscator())->decode($hash);
}

function fmsMenuSvgWithClass(?string $icon, string $class): string
{
  /* Ikon kosong memakai ikon default Phosphor Duotone lokal. */
  $defaultIconClass = 'ph-duotone ph-grid-four';

  $menuIcon = trim((string) $icon);
  if ($menuIcon === '') {
    return '<i class="' . esc($defaultIconClass . ' ' . $class . ' fms-menu-font-icon', 'attr') . '"></i>';
  }

  $menuIcon = html_entity_decode($menuIcon, ENT_QUOTES | ENT_HTML5);
  $menuIcon = trim($menuIcon);
  if ($menuIcon === '') {
    return '<i class="' . esc($defaultIconClass . ' ' . $class . ' fms-menu-font-icon', 'attr') . '"></i>';
  }

  if (str_starts_with(strtolower($menuIcon), '<svg')) {
    if (preg_match('/<svg[^>]*class="/', $menuIcon)) {
      return (string) preg_replace(
        '/<svg([^>]*)class="([^"]*)"/',
        '<svg$1class="$2 ' . esc($class, 'attr') . '"',
        $menuIcon,
        1,
      );
    }

    return (string) preg_replace(
      '/<svg/',
      '<svg class="' . esc($class, 'attr') . '"',
      $menuIcon,
      1,
    );
  }

  $iconClasses = preg_replace('/[^A-Za-z0-9_\- ]/', '', $menuIcon) ?? '';
  if ($iconClasses === '') {
    return '<i class="' . esc($defaultIconClass . ' ' . $class . ' fms-menu-font-icon', 'attr') . '"></i>';
  }

  return '<i class="' . esc($iconClasses . ' ' . $class . ' fms-menu-font-icon', 'attr') . '"></i>';
}
function fmsResolveMenuId(string $value): ?int
{
  /* Terima hashid ATAU integer murni — aman dipakai di semua controller backend. */
  $trimmed = trim($value);
  if (ctype_digit($trimmed) && $trimmed !== '' && (int) $trimmed > 0) {
    return (int) $trimmed;
  }
  return fmsDecodeId($trimmed);
}
