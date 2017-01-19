<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 18.01.17
 * Time: 21:48
 */

namespace Drupal\Tests\px_calendar_download;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\px_calendar_download\CalendarPropertyProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * @group px_calendar_download
 */
class CalendarPropertyProcessorTest extends UnitTestCase {

  /**
   * @var CalendarPropertyProcessor
   */
  protected $cpp;

  /**
   * @var ContentEntityInterface
   */
  protected $ce;

  /**
   * @inheritDoc
   */
  protected function setUp() {

    //Mock the content entity
    $this->ce = $this->getContentEntityMock();

    $this->cpp = new CalendarPropertyProcessor($this->getTokenMock(),
                                               $this->getTzpMock(),
                                               'date_field_reference',
                                               'date_field_uuid',
                                               $this->getTranslationManagerMock());

    parent::setUp();

  }

  /**
   * Tests calendar properties validation.
   *
   * We just test that it throws an error when it needs to,
   * and not that the drupal translation string service does its job properly
   * Because we mock the service we just return the unprocessed input
   *
   * @expectedException \Drupal\px_calendar_download\Exception\CalendarDownloadInvalidPropertiesException
   * @expectedExceptionMessageRegExp 'Missing needed property @propertyName.'
   *
   * @dataProvider                   propertyProvider
   */
  public function testCheckPropertiesMissingProperty($property) {

    $props = array_combine($this->cpp->getEssentialProperties(),
                           $this->cpp->getEssentialProperties());
    unset($props[$property]);
    $this->cpp->getCalendarProperties([$property], $this->ce, 'http');
  }

  /**
   * Test failing validation with a missing date list
   *
   * @expectedException \Drupal\px_calendar_download\Exception\CalendarDownloadInvalidPropertiesException
   * @expectedExceptionMessageRegExp 'Missing needed property @propertyName.'
   */
  public function testWithEmptyDateList() {

    $this->cpp->getCalendarProperties(array_combine($this->cpp->getEssentialProperties(),
                                                    $this->cpp->getEssentialProperties()),
                                      $this->ce,
                                      'http');

  }

  /**
   * Test passing validation with all properties
   */
  public function testWithAllValidProperties() {

    $props = array_combine($this->cpp->getEssentialProperties(),
                           $this->cpp->getEssentialProperties());

    $expected = [
      'timezone' => 'timezone',
      'product_identifier' => 'product_identifier',
      'uuid' => 'uuid',
      'summary' => 'summary',
      'dates_list' =>
        [
          0 => 'I am rendered',
        ],
    ];

    $this->assertArrayEquals($expected,
                             $this->cpp->getCalendarProperties($props,
                                                               $this->getContentEntityWithDateTimeMock()));

  }

  /**
   * Test setting and getting essential properties
   */
  public function testGetSetEssentialProperties() {

    $data = ['property A', 'property B'];
    $this->cpp->setEssentialProperties($data);
    $this->assertArrayEquals($data, $this->cpp->getEssentialProperties());

  }

  /**
   * Data Provider building is done before set up, so we must also construct a
   * CPP here to get the essential properties
   *
   * @return array
   */
  public function propertyProvider() {

    $token = $this->getTokenMock();
    $tzp = $this->getTzpMock();
    $tr = $this->getTranslationManagerMock();
    $cpp = new CalendarPropertyProcessor($token,
                                         $tzp,
                                         'date_field_reference',
                                         'date_field_uuid',
                                         $tr);
    return [$cpp->getEssentialProperties()];
  }

  /**
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function getTokenMock() {
    $token = $this->getMockBuilder('Drupal\Core\Utility\Token')
                  ->disableOriginalConstructor()
                  ->getMock();
    $token->expects($this->any())
          ->method('replace')
          ->will($this->returnArgument(0));

    return $token;
  }

  /**
   *
   */
  private function getTzpMock() {
    $tzp = $this->getMockBuilder('Drupal\px_calendar_download\Timezone\TimezoneProviderInterface')
                ->getMock();
    $tzp->expects($this->any())
        ->method('getTimezoneString')
        ->will($this->returnValue('Europe/Zurich'));

    return $tzp;
  }

  /**
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function getTranslationManagerMock() {

    $tr = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationManager')
               ->disableOriginalConstructor()
               ->getMock();
    $tr->expects($this->any())
       ->method('translate')
       ->will($this->returnArgument(0));

    return $tr;
  }

  /**
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function getContentEntityMock() {

    $ce = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityInterface')
               ->getMock();
    $ce->expects($this->any())
       ->method('uuid')
       ->will($this->returnValue('i_am_the_uuid'));
    $ce->expects($this->any())
       ->method('getEntityTypeId')
       ->will($this->returnValue('node'));

    //method get needs to return some mocks
    $il = $this->getMockBuilder('Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList')
               ->disableOriginalConstructor()
               ->getMock();
    //returning a value that is not a instanceof DrupalDateTime will result in the datetime being skipped
    //this is enough to get our tests working here
    $il->expects($this->any())
       ->method('getValue')
       ->will($this->returnValue(['value' => NULL]));

    $ce->expects($this->any())
       ->method('get')
       ->will($this->returnValue($il));

    return $ce;
  }

  /**
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function getContentEntityWithDateTimeMock() {

    $ce = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityInterface')
               ->getMock();
    $ce->expects($this->any())
       ->method('uuid')
       ->will($this->returnValue('i_am_the_uuid'));
    $ce->expects($this->any())
       ->method('getEntityTypeId')
       ->will($this->returnValue('node'));

    //method get needs to return some mocks
    $il = $this->getMockBuilder('Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList')
               ->disableOriginalConstructor()
               ->getMock();

    $dt = $this->getMockBuilder('Drupal\Core\Datetime\DrupalDateTime')
               ->disableOriginalConstructor()
               ->getMock();
    $dt->expects($this->any())
       ->method('render')
       ->will($this->returnValue('I am rendered'));

    $il->expects($this->any())
       ->method('getValue')
       ->will($this->returnValue([['value' => $dt]]));

    $ce->expects($this->any())
       ->method('get')
       ->will($this->returnValue($il));

    return $ce;
  }

}
