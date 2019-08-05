<?php

namespace RcmTest\Entity;



use Rcm\Entity\PluginInstance;

/**
 * Unit Test for Plugin Instance
 *
 * Unit Test for Plugin Instance
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class PluginInstanceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Rcm\Entity\PluginInstance */
    protected $pluginInstance;

    /**
     * Setup for tests
     *
     * @return void
     */
    public function setup()
    {
        $this->pluginInstance = new PluginInstance('user123');
    }

    /**
     * Test Get and Set Instance ID
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testGetAndSetInstanceId()
    {
        $id = 4;

        $this->pluginInstance->setInstanceId($id);

        $actual = $this->pluginInstance->getInstanceId();

        $this->assertEquals($id, $actual);
    }

    /**
     * Test Get and Set Plugin Name
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testGetAndSetPlugin()
    {
        $name = 'myPlugin';

        $this->pluginInstance->setPlugin($name);

        $actual = $this->pluginInstance->getPlugin();

        $this->assertEquals($name, $actual);
    }

    /**
     * @deprecated <deprecated-site-wide-plugin>
     * Test Set Site Wide and test Is it a side wide
     *
     * @return void
     *
     * @covers     \Rcm\Entity\PluginInstance
     */
    public function testSetSiteWideAndIsSiteWide()
    {
        $this->pluginInstance->setSiteWide();

        $this->assertTrue($this->pluginInstance->isSiteWide());
    }

    /**
     * Test Get and Set Site Wide Display Name
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testGetAndSetDisplayName()
    {
        $name = 'This Is My Site Wide Plugin Name For Display';

        $this->pluginInstance->setDisplayName($name);

        $actual = $this->pluginInstance->getDisplayName();

        $this->assertEquals($name, $actual);
    }

    /**
     * Test Get and Set MD5
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testGetAndSetMd5()
    {
        $md5 = md5('This is my MD5 String to Check');

        $this->pluginInstance->setMd5($md5);

        $actual = $this->pluginInstance->getMd5();

        $this->assertEquals($md5, $actual);
    }

    /**
     * Test Get and Set Previous Instance
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testGetAndSetPreviousInstance()
    {
        $previous = new PluginInstance('user123');
        $previous->setInstanceId(987);

        $this->pluginInstance->setPreviousInstance($previous);

        $actual = $this->pluginInstance->getPreviousInstance();

        $this->assertEquals($previous->getInstanceId(), $actual);
    }

    /**
     * Test Get and Set Instance Config
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testGetAndSetInstanceConfig()
    {
        $expected = [
            'var1' => 1,
            'var2' => 2
        ];

        $this->pluginInstance->setInstanceConfig($expected);

        $actual = $this->pluginInstance->getInstanceConfig();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test Cloning of object
     *
     * @return void
     *
     * @covers \Rcm\Entity\PluginInstance
     */
    public function testClone()
    {
        $instanceId = 42;
        $instanceConfig = [
            'var1' => 1,
            'var2' => 2
        ];
        $displayName = 'Display Name One';

        $md5 = md5(serialize($instanceConfig));
        $plugin = 'MockPlugin';

        $this->pluginInstance->setInstanceId($instanceId);
        $this->pluginInstance->setInstanceConfig($instanceConfig);
        $this->pluginInstance->setDisplayName($displayName);
        $this->pluginInstance->setMd5($md5);
        $this->pluginInstance->setPlugin($plugin);
        $this->pluginInstance->setSiteWide(true); // @deprecated <deprecated-site-wide-plugin>

        $cloned = $this->pluginInstance->newInstance('user123');

        $this->assertEquals($instanceConfig, $cloned->getInstanceConfig());
        $this->assertEquals($displayName, $cloned->getDisplayName());
        $this->assertEquals($md5, $cloned->getMd5());
        $this->assertEquals($plugin, $cloned->getPlugin());
        $this->assertTrue($cloned->isSiteWide()); //@deprecated <deprecated-site-wide-plugin>
        $this->assertNotEquals($instanceId, $cloned->getInstanceId());
        $this->assertNull($cloned->getInstanceId());

        $pluginInstance = new PluginInstance('user123');

        $clone = $pluginInstance->newInstance('user123');

        $this->assertInstanceOf(\Rcm\Entity\PluginInstance::class, $clone);
    }

    public function getTestData()
    {
        $data = [];
        $data['plugin'] = 'NAME';
        $data['siteWide'] = true; // @deprecated <deprecated-site-wide-plugin>
        $data['displayName'] = 'DISPLAYNAME';
        $data['instanceConfig'] = ['test' => 'insconf'];
        $data['md5'] = 'MD5';
        //
        $data['previousInstance'] = new PluginInstance('user123');
        $data['previousInstance']->setInstanceId(123);
        $data['renderedCss'] = 'RENDCSS';
        $data['renderedJs'] = 'RENDJS';
        $data['renderedHtml'] = 'RENDHTML';
        $data['canCache'] = true;
        $data['editCss'] = 'EDITCSS';
        $data['editJs'] = 'EDITJS';
        $data['icon'] = 'ICON';
        $data['tooltip'] = 'TOOLTIP';

        return $data;
    }

    public function testUtilities()
    {
        $data = $this->getTestData();

        $obj1 = new PluginInstance('user123');

        $obj1->populate($data);

        $this->assertEquals($data['plugin'], $obj1->getPlugin());
        $this->assertEquals($data['siteWide'], $obj1->isSiteWide()); //@deprecated <deprecated-site-wide-plugin>
        $this->assertEquals($data['displayName'], $obj1->getDisplayName());
        $this->assertEquals($data['instanceConfig'], $obj1->getInstanceConfig());
        $this->assertEquals($data['md5'], $obj1->getMd5());
        $this->assertEquals(
            $data['previousInstance']->getInstanceId(),
            $obj1->getPreviousInstance()
        );
        $this->assertEquals($data['renderedCss'], $obj1->getRenderedCss());
        $this->assertEquals($data['renderedJs'], $obj1->getRenderedJs());
        $this->assertEquals($data['renderedHtml'], $obj1->getRenderedHtml());
        $this->assertEquals($data['canCache'], $obj1->getCanCache());
        $this->assertEquals($data['editCss'], $obj1->getEditCss());
        $this->assertEquals($data['editJs'], $obj1->getEditJs());
        $this->assertEquals($data['icon'], $obj1->getIcon());
        $this->assertEquals($data['tooltip'], $obj1->getTooltip());

        $data['saveData'] = ['testSave' => 'saveData'];

        $obj1->populate($data);

        $this->assertEquals($data['saveData'], $obj1->getInstanceConfig());
        $this->assertEquals(md5(serialize($data['saveData'])), $obj1->getMd5());

        // sync the data back up
        $data['md5'] = $obj1->getMd5();
        $data['instanceConfig'] = $obj1->getInstanceConfig();

        $json = json_encode($obj1);

        $this->assertJson($json);

        $iterator = $obj1->getIterator();

        $this->assertInstanceOf('\ArrayIterator', $iterator);

        $array = $obj1->toArray();

        $this->assertEquals($data['plugin'], $array['plugin']);
        $this->assertEquals($data['siteWide'], $array['siteWide']); // @deprecated <deprecated-site-wide-plugin>
        $this->assertEquals($data['displayName'], $array['displayName']);
        // @todo this breaks in travis?
        // $this->assertEquals($data['instanceConfig'], $array['instanceConfig']);
        $this->assertEquals($data['md5'], $array['md5']);
        $this->assertEquals($data['renderedCss'], $array['renderedCss']);
        $this->assertEquals($data['renderedJs'], $array['renderedJs']);
        $this->assertEquals($data['renderedHtml'], $array['renderedHtml']);
        $this->assertEquals($data['canCache'], $array['canCache']);
        $this->assertEquals($data['editCss'], $array['editCss']);
        $this->assertEquals($data['editJs'], $array['editJs']);
        $this->assertEquals($data['icon'], $array['icon']);
        $this->assertEquals($data['tooltip'], $array['tooltip']);
    }
}
