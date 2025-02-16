<div class="tournament-header">
    <img src="../images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
         alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
         class="game-icon"
         onerror="this.src='../images/default-game-icon.png'">
    <span class="status-badge <?php echo $tournament['status']; ?>">
        <?php echo ucfirst($tournament['status']); ?>
    </span>
</div>

<div class="tournament-body">
    <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
    <div class="tournament-meta">
        <div class="meta-item">
            <i class="bi bi-controller"></i>
            <span><?php echo htmlspecialchars($tournament['game_name']); ?></span>
        </div>
        <div class="meta-item">
            <i class="bi bi-person-badge"></i>
            <?php 
            if (!empty($tournament['gamemaster_names'])) {
                echo htmlspecialchars($tournament['gamemaster_names']);
            } else {
                echo '<span class="unassigned">Unassigned</span>';
            }
            ?>
        </div>
        <div class="meta-item">
            <i class="bi bi-calendar3"></i>
            <span><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?></span>
        </div>
    </div>
    
    <div class="tournament-stats">
        <div class="stat-item">
            <div class="stat-value"><?php echo $tournament['team_count']; ?></div>
            <div class="stat-label">Teams</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo $tournament['match_count']; ?></div>
            <div class="stat-label">Matches</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">₱<?php echo number_format($tournament['prize_pool']); ?></div>
            <div class="stat-label">Prize Pool</div>
        </div>
    </div>
</div>

<div class="tournament-actions">
    <button class="btn-view" onclick="location.href='view_tournament.php?id=<?php echo $tournament['id']; ?>'">
        <i class="bi bi-eye"></i> View Details
    </button>
    <button class="btn-edit" onclick="location.href='edit_tournament.php?id=<?php echo $tournament['id']; ?>'">
        <i class="bi bi-pencil"></i>
    </button>
    <button class="btn-delete" onclick="confirmDelete(<?php echo $tournament['id']; ?>, '<?php echo htmlspecialchars($tournament['name'], ENT_QUOTES); ?>')">
        <i class="bi bi-trash"></i>
    </button>
</div> 