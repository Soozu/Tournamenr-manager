<div class="card-header">
    <img src="../images/games/<?php echo $game['icon']; ?>" alt="<?php echo $game['name']; ?>" class="game-icon">
    <span class="status-badge"><?php echo $game['status']; ?></span>
</div>

<div class="card-body">
    <h3 class="game-name"><?php echo $game['name']; ?></h3>
    
    <div class="game-meta">
        <span><i class="bi bi-controller"></i> <?php echo htmlspecialchars($game['name']); ?></span>
        <span><i class="bi bi-calendar-plus"></i> Added <?php echo date('M d, Y', strtotime($game['created_at'])); ?></span>
    </div>

    <div class="stats-container">
        <div class="stat-item">
            <div class="stat-value"><?php echo $game['tournament_count']; ?></div>
            <div class="stat-label">TOURNAMENTS</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo $game['team_count']; ?></div>
            <div class="stat-label">TEAMS</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo $game['match_count']; ?></div>
            <div class="stat-label">MATCHES</div>
        </div>
    </div>
</div>

<div class="card-actions">
    <button class="btn-view" onclick="viewGame(<?php echo $game['id']; ?>)">
        <i class="bi bi-eye"></i> View Details
    </button>
    <button class="btn-edit" onclick="editGame(<?php echo $game['id']; ?>)">
        <i class="bi bi-pencil"></i>
    </button>
    <button class="btn-delete" onclick="confirmDelete(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['name'], ENT_QUOTES); ?>')">
        <i class="bi bi-trash"></i>
    </button>
</div> 