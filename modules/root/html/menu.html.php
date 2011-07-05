<?php if (count($items) > 0): ?>
    <ul>
        <?php foreach ($items as $item): ?>
            <li>
                <img src="<?php echo EOZE_BASE_URL ?>images/icons/<?php echo $item['img'] ?>" class="<?php echo $item['iconClass'] ?>" <?php ?>
                     <?php if (isset($item['imgAlt'])): ?>alt="<?php echo $item['imgAlt'] ?>"<?php endif ?> />
                     <?php if (isset($item['action'])): ?>
                    <a href="javascript:<?php echo $item['action'] ?>">
                    <?php else: ?>
                        <a href="javascript:Oce.cmd('<?php echo $item['module'] ?>', 'open')()">
                        <?php endif ?>
                        <?php echo $item['title'] ?>
                    </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php else: ?>
    <p><?php _('Ce menu est vide. Peut-être cela indique-t-il un problème de configuration ?') ?></p>
<?php endif ?>
