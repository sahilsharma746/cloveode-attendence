<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();
$storage = new Storage();

$user = $auth->getUser();
$allUsers = $storage->getAllUsers();

$pageTitle = 'Organization';
$showSidebar = true;
include __DIR__ . '/includes/header.php';
?>
<main class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Organization</h1>
                <p class="page-subtitle">Meet your team members</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Team Members (<?php echo count($allUsers); ?>)
                </h3>
            </div>
            <div class="card-content">
                <?php if (empty($allUsers)): ?>
                    <div class="empty-state">
                        <p>No team members found</p>
                    </div>
                <?php else: ?>
                    <div class="organization-grid">
                        <?php foreach ($allUsers as $teamMember): ?>
                            <div class="organization-card">
                                <div class="org-member-avatar">
                                    <?php echo getUserAvatar($teamMember, 'large'); ?>
                                </div>
                                <div class="org-member-info">
                                    <h3 class="org-member-name"><?php echo htmlspecialchars($teamMember['full_name']); ?></h3>
                                    <?php if (!empty($teamMember['title'])): ?>
                                        <p class="org-member-title"><?php echo htmlspecialchars($teamMember['title']); ?></p>
                                    <?php endif; ?>
                                
                                    <div class="org-member-badges">
                                        <span class="badge <?php echo $teamMember['role'] === 'admin' ? 'badge-red' : 'badge-blue'; ?> capitalize">
                                            <?php echo $teamMember['role']; ?>
                                        </span>
                                        <?php if ($teamMember['id'] == $user['id']): ?>
                                            <span class="badge badge-green">You</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.organization-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    padding: 8px 0;
}

.organization-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.organization-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary);
}

.org-member-avatar {
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.org-member-info {
    width: 100%;
}

.org-member-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 8px 0;
}

.org-member-title {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0 0 4px 0;
    font-weight: 500;
}

.org-member-username {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0 0 12px 0;
}

.org-member-badges {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .organization-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }
    
    .organization-card {
        padding: 20px;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
