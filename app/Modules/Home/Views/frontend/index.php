<div style="width: 100%; height: 100%; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; font-family: system-ui, -apple-system, sans-serif; font-weight: 600;">
  <h2 style="margin-bottom: 5px;"><span style="color: #374151">FMS X</span> <span style="color: #ef4444">Codeigniter <?php echo \CodeIgniter\CodeIgniter::CI_VERSION ?></span></h2>
  <h5 style="margin-bottom: 0; margin-top: 0; font-weight: normal; color: gray;"><small>by <?php echo APP_AUTHOR ?></small></h5>
  <h6 style="margin-bottom: 0; margin-top: 0; font-weight: normal; color: gray;"><small>Licensi by <?php echo APP_SIGNATURE ?></small></h6>

  <?php if (ENVIRONMENT !== 'production') : ?>
    <div style="margin-top: 2rem; padding-top: 1rem; font-size: 0.65rem; font-weight: normal; text-align: center; color: #999; border-top: 1px solid #eee;">
      Page rendered in {elapsed_time} seconds. Memory usage: {memory_usage}
    </div>
  <?php endif; ?>
</div>