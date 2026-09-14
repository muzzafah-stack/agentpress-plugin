<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap agentpress-wrap">
    <header class="agentpress-header">
        <div class="agentpress-branding">
            <div class="agentpress-logo-badge">AP</div>
            <div>
                <h1>AgentPress <span class="agentpress-badge">v<?php echo esc_html(AGENTPRESS_VERSION); ?></span></h1>
                <p class="agentpress-tagline"><?php esc_html_e('Give AI a Home in WordPress.', 'agentpress'); ?></p>
            </div>
        </div>
        <div class="agentpress-header-meta">
            <span class="agentpress-status-dot online"></span>
            <span><?php esc_html_e('Bridge Active', 'agentpress'); ?></span>
        </div>
    </header>

    <nav class="nav-tab-wrapper agentpress-nav-tabs">
        <?php foreach ($tabs as $key => $title): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=agentpress&tab=' . $key)); ?>" 
               class="nav-tab <?php echo $active_tab === $key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($title); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="agentpress-tab-content">
        <?php
        $view_file = AGENTPRESS_PATH . 'src/Admin/Views/' . $active_tab . '.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            include AGENTPRESS_PATH . 'src/Admin/Views/connection.php';
        }
        ?>
    </div>
</div>
