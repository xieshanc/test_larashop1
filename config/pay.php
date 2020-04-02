<?php

return [
    'alipay' => [
        'app_id'         => '2016102400750619',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiVVZuFrGHcxwmJ+H4Q1ON9SDnC/uJGJ4eTDQubPwZ9lk6b8vyQxFsS5KCRRSyZrvCj7RXKaqyIJED9znJk0hUfGeQUL32hukt5jqluimubTgT3aCqSvhK6QzKc7krfNsSzZtUbTHgTR64RUSHIDlISPuSTEgjNlNQ0UT5VoxOZoYa5r/oEsrB+ls0/ONX8DcTpA/DvzsaQP//Fq+RUNE6bNkcrqOhXtRnCnVj0dP7oYt/fJoYetmkMVrg9XLzmUW/6uJaowBq7X0prYiQ6RiqtU/jsc4dQHycMTzVi+ukVzCAAURUpjXiYNVD7vcOniQ0O2iW7snF8bMA1PbbnId8QIDAQAB',
        'private_key'    => 'MIIEogIBAAKCAQEAiWnkVo9ti6sozZEci7S0HfDTvUt+ViRWahcmHXoMZtacqrRn0vqzLkxeiMVQHunoy4yvCIE5/TetNHHVJhXpG8o8V3cy/gQmA3ZAiGzVahcsgQFa7VONbdj/3zZCL+qdwd5gzdXteerzIvYMTOpErk4Y2o8Q1Ry7wFMjgxcd2G9onGF0gXxM7NfAd57+koUOhzmaO8CjQWPLidA7FYX2vcASkVQYAnx4qhMQI2VCrCaHcMPrmscsHej+AeSLMxfKU4mt7TzpUGDrfiuxoQrC13qpdGjfwEh/pEscC3l/r6F/dCuuhBWslzVxW3tnooOVczkvRGBdtqG0cvVF31X65QIDAQABAoIBAF+q7IDjLFYv3KF5pNETBs9NimzgsV1RwSPtZ+jfvuCeC8bBAQOe7L6QBsTb8lMDcMK9+GJIbdIAQEMh+U6htIBXhTkz0LmOs9R3MKiKdO5+ocpS4jYWlIFksYd6pAvuINUHeIf1p3bY/1tYx+mItDZSQvVu4z4r2f8lIrUyk+HJcXMY5pxNp2IsPdSKD2QM0j7LVR92wGSHz3XTw2Io0QVTgbfKWI1gJ3SlWY4+rItSYQSNBM3+dHK1kDK2gJyUwOGo5Bjqf0T0jyXMD7FZVfo7REdvn524TkKglfHQ+TcTmZ/1HBlNl8by4wj14ZDViMeBmEcKMkx7CtCpYwOkokECgYEAvbLw++PSgFJ4ollI+AJrxP/WxowYOmn3//+6OzuinQxTlToNEKWJO/lR7LaQJ5NEaF4ORlpNeU/srR67tzZGcqbth5TI4tuch2gXHn5/PVuJAS3Rmo2cZZF3oJrmO3PcGSs1MJkIHVG7s2y0LPTxlfckfC1d9GdO8klPyJ0CeXECgYEAuXDKS3FgXjO1df02BFRGstxldXMkMxKLrkmEP63U5topA9KLYdTv1/ITUT6WcOQAtBahmLB8d7kQLaxG9K5EBdnVZbJ5SXqqO7Wl2xWctfgARqaod4NMdMVmLTrRyVpcmrVYs5OjyTodoYVb6P+0eO2oCzD7/xWDobez9J2Y/rUCgYACkIa9gQj5fyPuXfKkl7PJSAQpTv+M2p3lshlcb6P1J5OBsvU2QmamjupSnu03+7+zAYKD5PijKTdz57R195/gMO2JJbPJjVqCYh/d46vosnIZt/rbcgqpPNw3KaDvMNPK61CexZLcgpxdAp009mLFuj+eBib9BOCGWgNgHO9JoQKBgDo/zauI/4ZXo8ZeNVuCxXpldJFCC7rnNm0Wtq3yApBJMtz6g8HFS1l/tsWsbUEkvXvUKAp4VYCd5xERrnApVUCpPjjlqOeF2ndLBcAdt84JCr0mmICCA8AiSYgnk8I12iTWlfhChg4tbSVRaJ91wtDBhimNx3pG3cQsmiBaaLaJAoGANJbhlVd0fWbaK+8Rf/0rV0hFr23eAAz+O3+GLZdMox59+PiPQlUCJDnFyjB8+CSbKD36WLOz+woPHossaMvxafxybN8X/cIPttiqBw2EH05SN6CblfhzIU/iSa0cNC36mgzd/W4/QsnNKJFSDIMHrK71kmFL7eQnWivM58no+/w=',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];
