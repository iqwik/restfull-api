<?php
// реализация вставки методом POST + отправка уведомления на почту
class Brif extends Model
{
    private static $labels = [
        'name' => 'Имя',
        'company' => 'Название компании, и web-сайт (если имеется)',
        'project' => 'Описание проекта',
        'budget' => 'Примерный бюджет',
        'period' => 'Желаемые сроки',
        'email' => 'E-mail',
        'phone' => 'Телефон',
        'website' => 'Тип сайта',
        'purpose' => 'Цель создания сайта',
        'customers' => 'Основные потребители вашей продукции',
        'opponents' => 'Сайты конкурентов',
        'like_sites' => 'Сайты, которые нравятся',
        'colors' => 'Цвета, которые желательно использовать',
    ];

    protected static function POST()
    {
        if (empty(parent::$action) || parent::$action != 'create')
            return [ 'code' => 405, 'data' => [ 'error' => self::$action ] ];

        try
        {
            Db::instance()->Insert(parent::$table, parent::$requestData);
            $res = [ 'code' => 201, 'data' => [ 'action' => self::$action ] ];
        }
        catch (Exception $e)
        {
            $res = [ 'code' => 400, 'data' => [ 'error' => $e->getMessage() ] ];
        }

        if ($res['code'] === 201)
        {
            $content = '';
            foreach (parent::$requestData as $k => $v)
            {
                $label = self::$labels[$k];
                $content .= "{$label}: <b>{$v}</b><br>";
            }

            try
            {
                Email::send(App::$config['smtp']['admin_email'], 'Заполнен Новый Бриф', $content);
                $res['data'] = [ 'email' => 'send' ];
            }
            catch (Exception $e)
            {
                $res = [ 'code' => 500, 'data' => [ 'error' => $e->getMessage() ] ];
            }
        }
        return $res;
    }
}