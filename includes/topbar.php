<style>
.topbar-affiliate {
    width: 100%;
    background: #fff;
    box-shadow: 0 2px 8px rgba(44,62,80,0.04);
    border-radius: 0 0 18px 18px;
    padding: 0.7rem 2.5rem 0.7rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1.2rem;
    position: relative;
    z-index: 100;
}
.topbar-affiliate .icon-btn {
    background: none;
    border: none;
    color: #222;
    font-size: 1.25rem;
    margin: 0 0.2rem;
    position: relative;
    transition: color 0.2s;
    outline: none;
    cursor: pointer;
}
.topbar-affiliate .icon-btn:hover {
    color: #1976d2;
}
.topbar-affiliate .notif-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #fc5c7d;
    color: #fff;
    font-size: 0.7rem;
    border-radius: 50%;
    padding: 2px 6px;
    font-weight: bold;
}
.topbar-affiliate .dropdown {
    position: relative;
    display: inline-block;
}
.topbar-affiliate .dropdown-menu {
    display: none;
    position: absolute;
    top: 38px;
    right: 0;
    min-width: 220px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(44,62,80,0.10);
    padding: 10px 0;
    z-index: 200;
    border: 1px solid #f0f0f0;
    animation: fadeIn 0.2s;
}
.topbar-affiliate .dropdown.open .dropdown-menu {
    display: block;
}
.topbar-affiliate .dropdown-menu a, .topbar-affiliate .dropdown-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 22px;
    color: #222;
    text-decoration: none;
    font-size: 1rem;
    transition: background 0.15s;
    cursor: pointer;
}
.topbar-affiliate .dropdown-menu a:hover, .topbar-affiliate .dropdown-menu .dropdown-item:hover {
    background: #f5f8ff;
}
.topbar-affiliate .dropdown-menu i {
    font-size: 1.2rem;
    min-width: 22px;
    text-align: center;
}
.topbar-affiliate .profile {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    color: #222;
    font-weight: 500;
    font-size: 1.08rem;
    cursor: pointer;
    background: #f5f6fa;
    border-radius: 12px;
    padding: 6px 18px 6px 12px;
    box-shadow: 0 2px 8px rgba(44,62,80,0.04);
}
.topbar-affiliate .profile .profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    line-height: 1.1;
}
.topbar-affiliate .profile .profile-name {
    font-weight: 600;
    font-size: 1.01rem;
}
.topbar-affiliate .profile .profile-role {
    font-size: 0.92rem;
    color: #888;
    font-weight: 400;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<div class="topbar-affiliate">
    <div class="dropdown" id="dropdownNotif">
        <button class="icon-btn position-relative" onclick="event.preventDefault(); this.parentNode.classList.toggle('open');" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-badge">1</span>
        </button>
        <div class="dropdown-menu" style="min-width:220px;">
            <div class="dropdown-item" style="color:#888; font-weight:500; cursor:default;">
                <i class="fas fa-inbox"></i> Aucune notification
            </div>
        </div>
    </div>
        </span>
    </a>
    <a href="https://youtube.com/" target="_blank" class="icon-btn" title="YouTube"><i class="fab fa-youtube" style="color:#ff5e5e;"></i></a>
    <a href="https://t.me/" target="_blank" class="icon-btn" title="Telegram"><i class="fab fa-telegram-plane" style="color:#4099ff;"></i></a>
    <div class="dropdown" id="dropdownWhatsapp">
        <button class="icon-btn" onclick="event.preventDefault(); this.parentNode.classList.toggle('open');" title="WhatsApp">
            <i class="fab fa-whatsapp" style="color:#25d366;"></i>
            <i class="fas fa-chevron-down" style="font-size:0.8rem; color:#aaa; margin-left:2px;"></i>
        </button>
        <div class="dropdown-menu">
            <a href="https://wa.me/212600000001" target="_blank"><i class="fas fa-headset"></i> Service de support</a>
            <a href="https://wa.me/212600000002" target="_blank"><i class="fas fa-sync-alt"></i> Service de change</a>
            <a href="https://wa.me/212600000003" target="_blank"><i class="fas fa-credit-card"></i> Service de paiement</a>
            <a href="https://wa.me/212600000004" target="_blank"><i class="fas fa-warehouse"></i> Service de stock</a>
            <a href="https://wa.me/212600000005" target="_blank"><i class="fas fa-lightbulb"></i> Service de creative</a>
        </div>
    </div>
    <div class="dropdown" id="dropdownProfile">
        <div class="profile" onclick="event.preventDefault(); this.parentNode.classList.toggle('open');">
            <i class="fas fa-user-circle" style="font-size:1.5rem;"></i>
            <div class="profile-info">
                <span class="profile-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Profil'; ?></span>
                <span class="profile-role">affiliate</span>
            </div>
            <i class="fas fa-chevron-down" style="font-size:0.95em; color:#aaa;"></i>
        </div>
        <div class="dropdown-menu" style="min-width:180px;">
            <div class="dropdown-item" style="font-size:0.98rem; color:#888; font-weight:600; cursor:default;">
                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Profil'; ?>
            </div>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>
<script>
// Fermer les dropdowns au clic extérieur
window.addEventListener('click', function(e) {
    document.querySelectorAll('.topbar-affiliate .dropdown').forEach(function(drop) {
        if (!drop.contains(e.target)) drop.classList.remove('open');
    });
});
</script> 