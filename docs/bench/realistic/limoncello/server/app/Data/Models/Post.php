<?php namespace App\Data\Models;

use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Application\ModelInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Flute\Types\DateTimeType;

/**
 * @package App
 */
class Post implements ModelInterface
{
    /** Table name */
    const TABLE_NAME = 'posts';

    /** Primary key */
    const FIELD_ID = 'id_post';

    /** Field name */
    const FIELD_ID_USER = User::FIELD_ID;

    /** Field name */
    const FIELD_TITLE = 'title';

    /** Field name */
    const FIELD_TEXT = 'text';

    /** Field name */
    const FIELD_CREATED_AT = 'created_at';

    /** Field name */
    const FIELD_UPDATED_AT = 'updated_at';

    /** Field name */
    const FIELD_DELETED_AT = 'deleted_at';

    /** Relationship name */
    const REL_USER = 'user';

    /**
     * @inheritdoc
     */
    public static function getTableName(): string
    {
        return static::TABLE_NAME;
    }

    /**
     * @inheritdoc
     */
    public static function getPrimaryKeyName(): string
    {
        return static::FIELD_ID;
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeTypes(): array
    {
        return [
            self::FIELD_ID         => Type::INTEGER,
            self::FIELD_ID_USER    => Type::INTEGER,
            self::FIELD_TITLE      => Type::STRING,
            self::FIELD_TEXT       => Type::TEXT,
            self::FIELD_CREATED_AT => DateTimeType::NAME,
            self::FIELD_UPDATED_AT => DateTimeType::NAME,
            self::FIELD_DELETED_AT => DateTimeType::NAME,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeLengths(): array
    {
        return [
            self::FIELD_TITLE => 255,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationships(): array
    {
        return [
            // sample relationships are below
            RelationshipTypes::BELONGS_TO => [
                self::REL_USER  => [User::class, self::FIELD_ID_USER, User::REL_POSTS],
            ],
//            RelationshipTypes::HAS_MANY   => [
//                self::REL_COMMENTS => [Comment::class, Comment::FIELD_ID_POST, Comment::REL_POST],
//            ],
//            RelationshipTypes::BELONGS_TO_MANY => [
//                self::REL_FILES => [
//                    File::class,
//                    PostFile::TABLE_NAME,
//                    PostFile::FIELD_ID_POST,
//                    PostFile::FIELD_ID_MEDIA_FILE,
//                    File::REL_POSTS,
//                ],
//            ],
        ];
    }
}
