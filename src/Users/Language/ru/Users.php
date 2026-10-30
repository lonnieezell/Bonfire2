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
    'savePerms' => 'Сохранить',

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

    // settings
    'registration'                 => 'Регистрация',
    'allowRegistration'            => 'Разрешить регистрацию',
    'allowRegistrationDesc'        => 'Пользователи могут сами регистрироваться на сайте. Если выключено, пользователей должен создавать администратор.',
    'emailActivation'              => 'Требовать подтверждение эл. почты',
    'emailActivationDesc'          => 'Отправлять новым пользователям код по эл. почте для подтверждения адреса.',
    'defaultGroup'                 => 'Группа по умолчанию',
    'defaultGroupDesc'             => 'Группа, в которую попадают новые зарегистрированные пользователи.',
    'login'                        => 'Вход',
    'rememberUsers'                => 'Запоминать пользователей',
    'rememberUsersDesc'            => 'Пользователям не нужно входить в систему при каждом посещении.',
    'rememberFor'                  => 'Запоминать на',
    'rememberSelect'               => 'Как долго запоминать...',
    'rememberHours'                => '{0, plural, one{# час} few{# часа} many{# часов} other{# часа}}',
    'rememberWeeks'                => '{0, plural, one{# неделю} few{# недели} many{# недель} other{# недели}}',
    'rememberMonths'               => '{0, plural, one{# месяц} few{# месяца} many{# месяцев} other{# месяца}}',
    'rememberYears'                => '{0, plural, one{# год} few{# года} many{# лет} other{# года}}',
    'email2fa'                     => 'Требовать 2FA после входа',
    'email2faDesc'                 => 'Отправлять пользователям код по эл. почте для подтверждения каждого входа.',
    'passwords'                    => 'Пароли',
    'passwordsDesc'                => 'Эти правила определяют, насколько надёжным должен быть пароль.',
    'minimumPasswordLength'        => 'Минимальная длина',
    'minimumPasswordLengthDesc'    => 'Предлагается минимум 8. Рекомендуется 10.',
    'validatorComposition'         => 'Валидатор состава',
    'validatorCompositionDesc'     => 'В основном проверяет требования к длине пароля.',
    'validatorNothingPersonal'     => 'Валидатор личных данных',
    'validatorNothingPersonalDesc' => 'Проверяет пароль на сходство с эл. почтой, логином и другими личными полями.',
    'validatorDictionary'          => 'Словарный валидатор',
    'validatorDictionaryDesc'      => 'Проверяет пароль по списку почти из 600 000 утёкших паролей.',
    'validatorPwned'               => 'Валидатор Pwned',
    'validatorPwnedDesc'           => 'Использует сторонний сайт (haveibeenpwned.com) для проверки пароля по миллионам утёкших паролей.',
    'noValidators'                 => 'Ни один валидатор не включён: пароли должны соответствовать только минимальной длине.',
    'validatorsOverlap'            => 'Словарный валидатор и валидатор Pwned дублируют друг друга. Достаточно одного из них.',
    'avatars'                      => 'Аватары',
    'avatarNameBasis'              => 'Инициалы из',
    'avatarNameBasisDesc'          => 'Используется для инициалов в аватаре, если нет изображения.',
    'fullName'                     => 'Полное имя',
    'useGravatar'                  => 'Использовать Gravatar',
    'useGravatarDesc'              => 'Использовать Gravatar (gravatar.com), если пользователь не загрузил аватар.',
    'gravatarDefault'              => 'Стиль Gravatar по умолчанию',
    'gravatarDefaultDesc'          => 'Примеры каждого стиля смотрите на gravatar.com.',
    'gravatarSelect'               => 'Выберите стиль по умолчанию....',
    'gravatarMysteryPerson'        => 'таинственный человек',
    'avatarResize'                 => 'Уменьшать при загрузке',
    'avatarResizeDesc'             => 'Уменьшать изображения аватаров, загруженные пользователями.',
    'avatarSize'                   => 'Максимальные размеры',
    'avatarSizeDesc'               => 'В пикселях; не меньше {0}.',
    // dashboard stat
    'usersInRecycler' => 'Пользователи в корзине',

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
