<?php use App\Core\View; ?>
<section class="card narrow">
    <h1><?= View::e($title) ?></h1>
    <p><?= View::e($message) ?></p>
    <p><a href="/">Back to the queue</a></p>
</section>
