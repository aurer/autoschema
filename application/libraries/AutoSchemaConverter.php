<?php

class AutoSchemaConverter
{

  /*
   *
   * Given an uploaded XML config this will convert it to a php AutoSchema version
   *
   * @param string $file_path
   *
   * @returns string
   *
   */
  public static function convert_file($file_path)
  {
    if( !file_exists($file_path) ){
      exit("File not found: '$file_path'");
    }
    $result = "";
    $xml = '<xml>';
    $xml .= file_get_contents($file_path);
    $xml .= '</xml>';
    foreach (simplexml_load_string($xml)->section as $section) {
      $result .= AutoSchemaConverter::convert_section_xml( $section->asXML() );
    }
    return $result;
  }

  /*
   *
   * Given an XML config section this will convert it to a php AutoSchema version
   *
   * @param string $source
   *
   * @returns string
   *
   */
  public static function convert_section_xml($source)
  {
    $xml = simplexml_load_string($source);
    $name = $xml->table->attributes()->name;
    $has_timestamps = false;

    $php = 'AutoSchema::table("' . $name . '", function($table){' . "\n";
    foreach( $xml->table->field as $field ){
      $attr = $field->attributes();
      if( $attr->name == 'ref' ) $attr->name = 'id';
      if( $attr->name == 'created' ) $attr->name = 'created_at';
      if( $attr->name == 'updated' ) $attr->name = 'updated_at';

      $php .= "\t" . '$table->' . self::translate($attr->type) . '("' . $attr->name . '")';
      if( $attr->label != '' ){
        $php .= '->label("' . $attr->label . '")';
      }
      if( $attr->required != '' ){
        $php .= '->rules("required")';
      }
      $php .= ";\n";
    }
    $php .= '});' . "\n\n";
    
    return $php;
  }

  /*
   *
   * Translates bewteen database type definitions
   *
   * @param string $value
   *
   * @returns string
   *
   */
  private static function translate($value)
  {
    $translations = array(
      'varchar'   => 'string',
      'serial'     => 'increments',
      'int'       => 'integer',
      'text'      => 'text',
      'boolean'   => 'boolean',
      'datetime'  => 'timestamp',
      'default'   => 'varchar',
    );
    if( array_key_exists(trim($value),  $translations))
    {
      return $translations[trim($value)];
    }
    else
    {
      return $translations['default']; 
    }
  }
}
