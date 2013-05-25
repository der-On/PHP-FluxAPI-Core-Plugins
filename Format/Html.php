<?php
namespace Plugins\FluxAPI\Format;


class Html extends Xml
{
    /**
     * Returns the file extension of the format.
     *
     * @return string
     */
    public static function getExtension()
    {
        return 'html';
    }

    /**
     * Returns the mime-type of the format.
     *
     * @return string
     */
    public static function getMimeType()
    {
        return 'text/html';
    }

    public static function encode($data, array $options = NULL)
    {
        if (is_string($data)) {
            return $data;
        } else {
            if (empty($options)) {
                $options = array('root' => 'html');
            } else {
                $options['root'] = 'html';
            }

            return parent::encode($data, $options);
        }
    }
}