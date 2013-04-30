<?php

class FBMock_TestDoubleCreator {
  public final function createTestDoubleFor(
      $class_name,
      array $interfaces = array(),
      array $traits = array(),
      $method_checker = null) {

    static $class_name_to_mock_count;
    FBMock_Utils::assertString($class_name);
    if (!class_exists($class_name) && !interface_exists($class_name)) {
      throw new FBMock_TestDoubleException(
        "Attempting to mock $class_name but $class_name isn't loaded."
      );
    }

    if (!isset($class_name_to_mock_count[$class_name])) {
      $class_name_to_mock_count[$class_name] = 0;
    }

    $mock_class_name = FBMock_Utils::mockClassNameFor(
      $class_name,
      $interfaces,
      $traits,
      $class_name_to_mock_count[$class_name]++
    );

    $class_generator_class = FBMock_Config::get()->getClassGenerator();
    $class_generator = new $class_generator_class();
    $ref_class = new ReflectionClass($class_name);

    if ($ref_class->isInternal() && !FBMock_Utils::isHPHP()) {
      throw new FBMock_TestDoubleException(
        "Trying to mock PHP internal class $class_name. Mocking of internal ".
        "classes is not supported in Zend."
      );
    }

    $code = $class_generator->generateCode(
      $ref_class,
      $mock_class_name,
      $interfaces,
      $traits,
      $method_checker
    );
    eval($code);

    $mock_object = (new ReflectionClass($mock_class_name))
      ->newInstanceWithoutConstructor();

    $mock_object->__mockImplementation =
      new FBMock_MockImplementation($class_name);

    $this->postCreateHandler($mock_object);
    return $mock_object;
  }

  // Override to add custom logic to mocks after they are created
  protected function postCreateHandler($double) { }
}
