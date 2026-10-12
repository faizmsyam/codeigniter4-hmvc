<?php
namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
class DebugKeyCmd extends BaseCommand {
  protected $group='FMS'; protected $name='fms:debugkey';
  public function run(array $params) {
    // 1. Allowed MIME types from settings
    $svc = new \App\Modules\Storage\Services\FMSStorageObjectService();
    $cfg = config(\App\Modules\Storage\Config\FMSStorage::class);
    $mimes = $cfg->allowedMimeTypes();
    CLI::write('Allowed MIMEs: '.json_encode($mimes));
    $exts = [];
    foreach ($mimes as $m) $exts[] = $cfg->extensionForMime($m);
    CLI::write('Extensions from allowedMimes: '.json_encode(array_values(array_unique($exts))));
    
    // 2. Extension map from FMSStorage static config
    CLI::write('Static mimeExtensions: '.json_encode($cfg->mimeExtensions));
    
    // 3. Test safeKey with various extensions
    $ref = new \ReflectionMethod($svc, 'safeKey'); $ref->setAccessible(true);
    $cases = [
      'test.jpg','test.png','test.pdf','test.webp',
      'test.docx','test.xlsx','test.txt','test.zip',
      'test.mp4','test.mov','test.mp3',
      'test.JPEG','test.PNG','test.PDF',
    ];
    foreach ($cases as $k) {
      try { $ref->invoke($svc, $k); CLI::write("safeKey OK     $k", 'green'); }
      catch (\Throwable $e) { CLI::write("safeKey FAIL  $k -> ".$e->getMessage(), 'red'); }
    }
  }
}
