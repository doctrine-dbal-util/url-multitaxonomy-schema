<?php

namespace DoctrineDbalUtil\UrlMultiTaxonomy\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class SchemaBuilder
{
    protected $schema;

    function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    function UrlTable() {
        $UrlTable = $this->schema->createTable("url");
        // $UrlTable->addColumn("uuid", "guid", ['default' => 'uuid_generate_v5(uuid_ns_url(), url.url)']);
        //^ Cannot use column's references in default expression
        $UrlTable->addColumn("uuid", "guid");
        $UrlTable->setPrimaryKey(["uuid"]);
        $UrlTable->addColumn("url", "string", ["length" => 1000]); // "customSchemaOptions" => ["unique" => true]]);
        $UrlTable->addUniqueIndex(["url"], "url_unique_url"); // Should not be useful, if uuid v5 primary key is used. Keept for debug.
        $UrlTable->addColumn("deleted", "datetime", ["notnull" => false]);
        return $UrlTable;
    }
    
    function OwnedUrlTable(Table $UrlTable) {
        $OwnedUrlTable = $this->schema->createTable("owned_url");
        // $OwnedUrlTable->addColumn("id", "integer", ["autoincrement" => true]);
        // $OwnedUrlTable->addColumn("uuid", "guid", ['default' => 'gen_random_uuid()']); // !!!!! This needs to be done directly in SQL! !!!!!
        $OwnedUrlTable->addColumn("uuid", "guid"); // Default value directly added in SQL!
        // https://www.google.ca/search?q=postgres+random+numbers
        // https://www.postgresql.org/docs/current/static/functions-math.html
        // https://www.postgresql.org/docs/current/static/pgcrypto.html
        //^ gen_random_uuid() returns uuid // Returns a version 4 (random) UUID.
        // https://www.postgresql.org/docs/current/static/datatype-uuid.html
        // https://packages.debian.org/en/postgresql-contrib
        // https://www.postgresql.org/docs/current/static/uuid-ossp.html
        // SELECT uuid_generate_v5(uuid_ns_url(), 'http://www.postgresql.org');
        // uuid_generate_v4()
        // Debian 9 for php interface to C uuid
        // https://packages.qa.debian.org/p/php-libsodium.html
        // https://packages.qa.debian.org/p/php-uuid.html
        // PHP uuid
        // https://packagist.org/packages/ramsey/uuid
        // https://packagist.org/packages/ramsey/uuid-doctrine
        $OwnedUrlTable->setPrimaryKey(["uuid"]);
        $OwnedUrlTable->addColumn("url_uuid", "guid");
        $OwnedUrlTable->addIndex(["url_uuid"], "owned_url_url");
        $OwnedUrlTable->addForeignKeyConstraint($UrlTable, ["url_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "url_uuid_fk");
        // $OwnedUrlTable->addColumn("url", "string", ["length" => 1000, "customSchemaOptions" => ["unique" => true]]);
        // $OwnedUrlTable->addColumn("url", "string", ["length" => 1000]);
        //^ $OwnedUrlTable->addUniqueIndex(["url"], "url_unique_url"); // Should be unicity for each user
        //^ Triggers function instead of index could be used to check unicity:
        //^ https://www.postgresql.org/docs/current/static/plpgsql-trigger.html
        //^ https://www.postgresql.org/docs/current/static/sql-createtrigger.html
        //^ Or the urls could be common, not owned and the ownership could be just on a table with a foreign key on the url table primary key.
        // $OwnedUrlTable->addIndex(["url"], "url_url");
        // should be made translatable
        // $OwnedUrlTable->addColumn("language", "string", ["length" => 10, "notnull" => false]);
        //^ not sure if this has to be keept
        //^ They may be a better way to do it.
        //^ maybe the problem is the form, which to not allow a null value
        //^ maybe it should be an array of languages, if more than one is possible.
        // should be made deleteable
        $OwnedUrlTable->addColumn("deleted", "datetime", ["notnull" => false]);
        return $OwnedUrlTable;
    }
    
    function LinkUrlUser(Table $OwnedUrlTable, Table $User) {
        $LinkUrlUser = $this->schema->createTable("link_owned_url_user");
        // $LinkUrlUser->addColumn("id", "integer", ["autoincrement" => true]);
        $LinkUrlUser->addColumn("uuid", "guid"); // owned url encoded with the user uuid
        $LinkUrlUser->setPrimaryKey(["uuid"]);
        $LinkUrlUser->addColumn("owned_url_uuid", "guid");
        $LinkUrlUser->addIndex(["owned_url_uuid"], "link_owned_url_user_url");
        $LinkUrlUser->addForeignKeyConstraint($OwnedUrlTable, ["owned_url_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "owned_url_uuid_fk");
        // $LinkUrlUser->addForeignKeyConstraint($OwnedUrlTable, ["url_id"], ["id"], [], "url_id_fk"); changed
        $LinkUrlUser->addColumn("user_uuid", "guid");
        $LinkUrlUser->addIndex(["user_uuid"], "link_owned_url_user_user");
        $LinkUrlUser->addForeignKeyConstraint($User, ["user_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "user_uuid_fk");
        // $LinkUrlUser->addForeignKeyConstraint($UserTable, ["taxonomy_id"], ["id"], [], "taxonomy_id_fk");
        $LinkUrlUser->addUniqueIndex(["owned_url_uuid", "user_uuid"], "link_url_user_unique_url_user");
        //^ Optional constraint of unicity, but essential here for link_owned_url_user.
        //^ https://www.postgresql.org/docs/9.2/static/ddl-constraints.html
        //^ TODO: why not a constraint?
        //^ Apparently DBAL supports only one column constaints: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html
        $LinkUrlUser->addColumn("deleted", "datetime", ["notnull" => false]);
        // $LinkUrlUser->setPrimaryKey(["url_id", "taxonomy_id"]);
        // BUG: ["onUpdate" => "CASCADE"] seems not supported for sqlite by dbal 2.5.8
        // TODO: schema could be a application ~constant usable from all command objects...
        return $LinkUrlUser;
    }
}
