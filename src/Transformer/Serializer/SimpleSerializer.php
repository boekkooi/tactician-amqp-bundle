<?php
namespace Boekkooi\Bundle\AMQP\Transformer\Serializer;

use Symfony\Component\Serializer\Encoder;
use Symfony\Component\Serializer\Normalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * A simple command serializer
 */
class SimpleSerializer extends Serializer
{
    public function __construct(array $normalizers = array(), array $encoders = array())
    {
        parent::__construct(
            [
                new Normalizer\GetSetMethodNormalizer()
            ],
            [
                new Encoder\JsonEncoder(),
                new Encoder\XmlEncoder(),
            ]
        );
    }
}
