<?php
use App\Core\View;
use App\Models\TicketStatus;
?>
<div class="board-head">
    <h1>Queue board</h1>
    <ul class="stats">
        <li><strong><?= (int) $stats['waiting'] ?></strong> waiting</li>
        <li><strong><?= (int) $stats['helping'] ?></strong> in progress</li>
        <li><strong><?= (int) $stats['resolved_today'] ?></strong> helped today</li>
        <li><strong><?= (int) $stats['avg_help_minutes'] ?> min</strong> avg</li>
    </ul>
</div>

<?php if (!$tickets): ?>
    <p class="empty card">Queue's empty. Nice work, <?= View::e($me) ?>.</p>
<?php else: ?>
    <ol class="queue">
        <?php foreach ($tickets as $ticket): ?>
            <li class="card status-<?= View::e($ticket->status->value) ?>">
                <div>
                    <p class="eyebrow">
                        <?= View::e($ticket->unitCode) ?> · joined <?= View::e($ticket->createdAt->format('g:i a')) ?>
                        · <?= View::e($ticket->status->label()) ?>
                        <?php if ($ticket->facilitator): ?> by <?= View::e($ticket->facilitator) ?><?php endif; ?>
                    </p>
                    <p class="name"><?= View::e($ticket->studentName) ?></p>
                    <p><?= View::e($ticket->topic) ?></p>
                </div>

                <form method="post" action="/tickets/<?= (int) $ticket->id ?>/<?= $ticket->status === TicketStatus::Waiting ? 'claim' : 'resolve' ?>">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                    <button type="submit" class="<?= $ticket->status === TicketStatus::Waiting ? '' : 'secondary' ?>">
                        <?= $ticket->status === TicketStatus::Waiting ? 'Claim' : 'Mark helped' ?>
                    </button>
                </form>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
