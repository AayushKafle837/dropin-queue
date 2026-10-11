<?php
use App\Core\View;
use App\Models\TicketStatus;
?>
<section class="card ticket">
    <p class="eyebrow"><?= View::e($ticket->unitCode) ?> · ticket #<?= (int) $ticket->id ?></p>

    <?php if ($ticket->status === TicketStatus::Waiting): ?>
        <h1>You're number <?= (int) $position ?></h1>
        <p class="big">
            <?= $estimate === 0 ? "You're next." : 'Roughly ' . (int) $estimate . ' minutes to go.' ?>
        </p>
    <?php elseif ($ticket->status === TicketStatus::Helping): ?>
        <h1><?= View::e($ticket->facilitator) ?> is on the way</h1>
        <p class="big">Wave so they can find you.</p>
    <?php else: ?>
        <h1>All done</h1>
        <p class="big">Hope that helped. Join again any time.</p>
    <?php endif; ?>

    <p class="muted">Your question: <?= View::e($ticket->topic) ?></p>
    <p class="muted small">This page refreshes every 20 seconds.</p>
</section>
