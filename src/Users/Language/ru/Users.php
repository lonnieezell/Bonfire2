<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

return [
    'usersModTitle'   => 'Пользователи',
    'editUser'        => 'Изменить Пользователя',
    'newUser'         => 'Создать Пользователя',
    'users'           => 'Пользователи',
    'user'            => 'пользователь',
    'userGenitive'    => 'пользователь',
    'userAccusative'  => 'пользователь',
    'usersAccusative' => 'пользователи',
    'permissions'     => 'Разрешения',

    // avatar
    'deleteImageConfirm' => 'Вы уверены, что хотите удалить изображение? Его невозможно восстановить',
    'deleteImage'        => 'Удалить загруженное изображение',

    // row_info
    'never' => 'никогда',

    // cards
    'cardDetails'     => 'Подробности',
    'cardPermissions' => 'Разрешения',
    'cardSecurity'    => 'Безопасность',

    // details
    'userDeletedOn'        => 'Этот пользователь был удален {0}.',
    'restoreUser'          => 'Восстановить пользователя',
    'email'                => 'Эл. почта',
    'username'             => 'Логин',
    'firstName'            => 'Имя',
    'lastName'             => 'Фамилия',
    'activated'            => 'Активный',
    'banned'               => 'Забанен',
    'enterBanReason'       => 'введите причину бана (будет показана при попытке входа в систему).',
    'groups'               => 'Группы',
    'selectGroups'         => 'Выберите одну или несколько групп, к которым будет принадлежать пользователь',
    'cannotAddAdminGroups' => 'Группы с правами администратора не могут быть добавлены или удалены с вашими текущими разрешениями',
    'groupListDisabled'    => 'Группы, к которым принадлежит пользователь (у вас нет прав на изменение списка)',
    'saveUser'             => 'Сохранить',

    // permissions
    'permsDetail' => 'Эти разрешения применяются в дополнение к любым разрешенным группами пользователей.
                Если у вас нет разрешения <em>{0}</em>,
                вы не сможете выбрать разрешения, связанные с управлением пользователями
                (если только они не были предоставлены ранее).',
    'savePerms'   => 'Сохранить',

    // security
    'changePass'     => 'Сменить Пароль',
    'updatePass'     => 'Обновить Пароль',
    'recentLogins'   => 'Последние входы',
    'date'           => 'Дата',
    'ipAddress'      => 'IP',
    'userAgent'      => 'Браузер',
    'success'        => 'Результат',
    'successYes'     => 'Успешно',
    'successNo'      => 'Неудачно',
    'noRecentLogins' => 'Попыток входа в систему не было',

    // filter
    'userRole'             => 'Роль',
    'userActiveQuestion'   => 'Активный?',
    'userActiveOptionsYes' => 'Активный',
    'userActiveOptionsNo'  => 'Неактивный',
    'userBannedQuestion'   => 'Забанен?',
    'userBannedOptionsYes' => 'Забанен',
    'userBannedOptionsNo'  => 'Не забанен',
    'lastActiveWintin'     => 'Последняя Активность',
    'labelDay'             => 'день',
    'labelDays'            => 'дня',
    'labelWeek'            => 'неделя',
    'labelWeeks'           => 'недели',
    'labelMonth'           => 'месяц',
    'labelMonths'          => 'месяца(ев)',
    'labelYear'            => 'год',
    'labelAnyTime'         => 'в любое время',
    'labelNever'           => 'никогда',
    'labelAll'             => 'показать все',
    'recycler'             => [
        'label'   => 'Пользователи',
        'columns' => [
            'id'         => 'ID',
            'username'   => 'Логин',
            'first_name' => 'Имя',
            'last_name'  => 'Фамилия',
            'email'      => 'Эл. почта',
        ],
    ],
    'searchUsers'   => 'Поиск по имени, email, логину',
    'allGroups'     => 'Все',
    'noUsersMatch'  => 'Пользователи не найдены',
    'noUsersHint'   => 'Попробуйте другой запрос или группу.',
    'selectUser'    => 'Выбрать {0}',
    'statusActive'  => 'Активен',
    'statusPending' => 'Ожидает',
    'statusBanned'  => 'Заблокирован',

    'headers' => [
        'user'        => 'Пользователь',
        'status'      => 'Статус',
        'id'          => 'ID',
        'username'    => 'Логин',
        'first_name'  => 'Имя',
        'last_name'   => 'Фамилия',
        'groups'      => 'Группы',
        'email'       => 'Эл. почта',
        'last_active' => 'Посл. Активность',
    ],
];
