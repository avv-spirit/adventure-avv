Adventure AVV — Admin PRO (Dark/Blue, fade-in, Projects CRUD + Filters + Pagination + Drag&Drop)

Установка
1) Создайте БД MySQL и импортируйте db.sql.
2) В config.php укажите доступы ($host,$db,$user,$pass).
3) Разместите содержимое папки на сервере как /admin.
4) Проверьте права на запись для /admin/uploads (chmod 775/777).
5) Логин: admin / admin123 — смените пароль.

Вставка проектов на главную (PHP):
<?php require __DIR__.'/admin/config.php'; $st=$pdo->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 12"); $projects=$st->fetchAll(); foreach($projects as $p){ echo '<div class=project-card>'; if($p['image']) echo '<img src="/admin/uploads/'.htmlspecialchars($p['image']).'">'; echo '<h3>'.htmlspecialchars($p['title']).'</h3>'; echo '<p>'.htmlspecialchars($p['description']).'</p>'; if($p['link']) echo '<a href="'.htmlspecialchars($p['link']).'" target=_blank>Смотреть</a>'; echo '</div>'; } ?>

Подсказки безопасности: HTTPS, ограничить доступ к /admin, лимит размера файлов в php.ini.