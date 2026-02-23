<?php
/**
 * topbar.php - Centralized Header for BexMedia "Swiss Minimalist" Design
 * Included after security.php to utilize global user variables.
 */
?>
<header>
    <div class="header-left">
        <?php if (isset($breadcrumb)): ?>
            <div class="breadcrumb" style="color: var(--text-muted); font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="home" size="14"></i> / <?= $breadcrumb ?>
            </div>
        <?php else: ?>
            <div class="search-bar">
                <i data-lucide="search" size="18" style="color: var(--text-muted)"></i>
                <input type="text" placeholder="Search data, reports, or employees...">
            </div>
        <?php endif; ?>
    </div>

    <div class="header-actions">
        <?php if (isset($header_extra)): ?>
            <div class="header-extra"><?= $header_extra ?></div>
        <?php endif; ?>
        
        <div class="header-icon-btn" style="position: relative; color: var(--text-muted); cursor: pointer; padding: 8px; border-radius: 10px; transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
            <i data-lucide="bell" size="20"></i>
            <span style="position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; background: #EF4444; border-radius: 50%; border: 2px solid white"></span>
        </div>

        <div style="width: 1px; height: 24px; background: var(--border-color); margin: 0 8px;"></div>
        
        <div class="user-profile" style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 4px 8px; border-radius: 12px; transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.03)'" onmouseout="this.style.backgroundColor='transparent'">
            <div style="text-align: right; line-height: 1.2;">
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--ice-1)"><?= htmlspecialchars($nama_user) ?></div>
                <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.02em;"><?= htmlspecialchars($jabatan_user ?: 'Staff') ?></div>
            </div>
            
            <?php 
            $final_foto = "";
            if (!empty($foto_user)) {
                // Determine if we need to go up a level (from dist)
                $check_path = (strpos($_SERVER['PHP_SELF'], '/dist/') !== false) ? "../" . $foto_user : $foto_user;
                if (file_exists($check_path)) {
                    $final_foto = $check_path;
                }
            }
            ?>

            <?php if (!empty($final_foto)): ?>
                <div class="avatar" style="background-image: url('<?= htmlspecialchars($final_foto) ?>'); background-size: cover; background-position: center; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 36px; height: 36px; border-radius: 50%;"></div>
            <?php else: ?>
                <div class="avatar" style="background: linear-gradient(135deg, #3B82F6, #8B5CF6); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.9rem; font-weight: 800; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 36px; height: 36px; border-radius: 50%;">
                    <?= strtoupper(mb_substr($nama_user, 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
