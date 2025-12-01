            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ((array)$extraJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
