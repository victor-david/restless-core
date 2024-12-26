<?php declare(strict_types=1);
namespace Restless\Log;

class PsrLogMessageProcessor implements ProcessorInterface
{
    protected $dateFormat;

    /**
     * Class constructor
     *
     * @param mixed $dateFormat Desired date format, or null to use the default.
     */
    public function __construct(?string $dateFormat = null)
    {
        $this->dateFormat = $dateFormat;
    }

    public function __invoke(LogMessageObject $msg)
    {
        $replacements = [];
        foreach ($msg->context as $key => $val)
        {
            $placeholder = '{' . $key . '}';
            if (strpos($msg->message, $placeholder) !== false)
            {
                if (is_null($val) || is_scalar($val))
                {
                    $replacements[$placeholder] = $val;
                }
                elseif ($val instanceof \DateTimeInterface)
                {
                    $replacements[$placeholder] = $val->format($this->dateFormat ?: \DateTimeInterface::ATOM);
                }
                elseif (is_object($val))
                {
                    $replacements[$placeholder] = Utility::getClass($val);
                }
                elseif (is_array($val))
                {
                    $replacements[$placeholder] =  Utility::jsonEncode($val);
                }
                else
                {
                    $replacements[$placeholder] = Utility::getType($val);
                }
            }
        }
        $msg->message = strtr($msg->message, $replacements);
    }
}
?>