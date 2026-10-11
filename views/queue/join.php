<?php use App\Core\View; ?>
<section class="hero">
    <h1>Stuck on something? Grab a spot.</h1>
    <p>Add yourself to the queue and a facilitator will come to you. Keep working while you wait.</p>
    <ul class="stats">
        <li><strong><?= (int) $stats['waiting'] ?></strong> waiting</li>
        <li><strong><?= (int) $stats['helping'] ?></strong> being helped</li>
        <li><strong>~<?= (int) $stats['avg_help_minutes'] ?> min</strong> per student</li>
    </ul>
</section>

<form method="post" action="/join" class="card" novalidate>
    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

    <label for="student_name">First name</label>
    <input id="student_name" name="student_name" maxlength="60" required
           value="<?= View::e($old['student_name'] ?? '') ?>"
           aria-invalid="<?= isset($errors['student_name']) ? 'true' : 'false' ?>">
    <?php if (isset($errors['student_name'])): ?><p class="error"><?= View::e($errors['student_name']) ?></p><?php endif; ?>

    <label for="unit_code">Unit code</label>
    <input id="unit_code" name="unit_code" maxlength="6" placeholder="CAB201" required
           value="<?= View::e($old['unit_code'] ?? '') ?>"
           aria-invalid="<?= isset($errors['unit_code']) ? 'true' : 'false' ?>">
    <?php if (isset($errors['unit_code'])): ?><p class="error"><?= View::e($errors['unit_code']) ?></p><?php endif; ?>

    <label for="topic">What do you need help with?</label>
    <textarea id="topic" name="topic" rows="3" maxlength="280" required
              placeholder="e.g. My loop prints one value too many"
              aria-invalid="<?= isset($errors['topic']) ? 'true' : 'false' ?>"><?= View::e($old['topic'] ?? '') ?></textarea>
    <?php if (isset($errors['topic'])): ?><p class="error"><?= View::e($errors['topic']) ?></p><?php endif; ?>

    <button type="submit">Join the queue</button>
</form>
