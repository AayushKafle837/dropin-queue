<?php use App\Core\View; ?>
<form method="post" action="/facilitator/login" class="card narrow">
    <h1>Facilitator sign-in</h1>
    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

    <?php if ($error): ?><p class="error"><?= View::e($error) ?></p><?php endif; ?>

    <label for="name">Your name</label>
    <input id="name" name="name" required maxlength="60" autocomplete="name">

    <label for="pin">Session PIN</label>
    <input id="pin" name="pin" type="password" required inputmode="numeric" autocomplete="off">

    <button type="submit">Open the board</button>
</form>
