<?php

namespace App\Models;

use CodeIgniter\Model;

class FMSMenuModel extends Model
{
  protected $table = 'c_menus';
  protected $primaryKey = 'id';

  protected $returnType = 'object';

  protected string $routePrefix = ROUTE_ADMIN;

  protected $allowedFields = [
    'id_parent',
    'name',
    'url',
    'icon',
    'position',
    'is_active',
    'target_blank',
  ];

  public function getActiveMenus()
  {
    $menus = $this->where('is_active', 1)
      ->orderBy('position', 'asc')
      ->findAll();


    foreach ($menus as $menu) {
      if ($menu->url && $menu->url !== '#' && !str_starts_with($menu->url, 'http')) {
        $menu->url = trim($this->routePrefix . '/' . trim($menu->url, '/'), '/');
      }
    }

    return $menus;
  }

  public function getMenuByUrl(string $url)
  {
    return $this->where('url', $url)
      ->where('is_active', 1)
      ->first();
  }

  public function getMenuParents(int $menuId): array
  {
    $breadcrumbs = [];

    while ($menuId) {
      $menu = $this->find($menuId);
      if (!$menu) {
        break;
      }

      array_unshift($breadcrumbs, $menu);
      $menuId = $menu->id_parent;
    }

    return $breadcrumbs;
  }
}
