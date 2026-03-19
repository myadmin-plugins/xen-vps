<?php

declare(strict_types=1);

namespace Detain\MyAdminXen\Tests;

use Detain\MyAdminXen\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Test suite for the Detain\MyAdminXen\Plugin class.
 *
 * Covers class structure, static properties, pure methods,
 * event handler signatures, and static analysis of DB/global usage.
 *
 * @coversDefaultClass \Detain\MyAdminXen\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Set up reflection instance for structural tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // ---------------------------------------------------------------
    // Class Structure Tests
    // ---------------------------------------------------------------

    /**
     * Verify the Plugin class can be instantiated.
     *
     * @covers ::__construct
     * @return void
     */
    public function testClassIsInstantiable(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Verify the Plugin class resides in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminXen', $this->reflection->getNamespaceName());
    }

    /**
     * Verify the Plugin class is not abstract.
     *
     * @return void
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Verify the Plugin class is not final (allows extension).
     *
     * @return void
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Verify the constructor takes no required parameters.
     *
     * @covers ::__construct
     * @return void
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    // ---------------------------------------------------------------
    // Static Property Tests
    // ---------------------------------------------------------------

    /**
     * Verify the $name static property exists and holds the expected value.
     *
     * @return void
     */
    public function testStaticPropertyName(): void
    {
        $this->assertTrue($this->reflection->hasProperty('name'));
        $this->assertSame('Xen VPS', Plugin::$name);
    }

    /**
     * Verify the $description static property exists and is a non-empty string.
     *
     * @return void
     */
    public function testStaticPropertyDescription(): void
    {
        $this->assertTrue($this->reflection->hasProperty('description'));
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Verify the $description property contains key Xen-related terms.
     *
     * @return void
     */
    public function testDescriptionContainsXenReferences(): void
    {
        $this->assertStringContainsString('Xen', Plugin::$description);
        $this->assertStringContainsString('hypervisor', Plugin::$description);
        $this->assertStringContainsString('xenproject.org', Plugin::$description);
    }

    /**
     * Verify the $help static property exists and is a string.
     *
     * @return void
     */
    public function testStaticPropertyHelp(): void
    {
        $this->assertTrue($this->reflection->hasProperty('help'));
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Verify the $module static property is set to 'vps'.
     *
     * @return void
     */
    public function testStaticPropertyModule(): void
    {
        $this->assertTrue($this->reflection->hasProperty('module'));
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * Verify the $type static property is set to 'service'.
     *
     * @return void
     */
    public function testStaticPropertyType(): void
    {
        $this->assertTrue($this->reflection->hasProperty('type'));
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Verify all static properties are public.
     *
     * @return void
     */
    public function testAllStaticPropertiesArePublic(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $property) {
            $prop = $this->reflection->getProperty($property);
            $this->assertTrue($prop->isPublic(), "Property \${$property} should be public");
            $this->assertTrue($prop->isStatic(), "Property \${$property} should be static");
        }
    }

    // ---------------------------------------------------------------
    // getHooks() Tests (Pure Method)
    // ---------------------------------------------------------------

    /**
     * Verify getHooks() returns an array.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Verify getHooks() returns a non-empty array.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksIsNotEmpty(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertNotEmpty($hooks);
    }

    /**
     * Verify getHooks() keys are prefixed with the module name.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksKeysArePrefixedWithModule(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $this->assertStringStartsWith(
                Plugin::$module . '.',
                $key,
                "Hook key '{$key}' should start with '" . Plugin::$module . ".'"
            );
        }
    }

    /**
     * Verify getHooks() contains expected hook entries.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('vps.settings', $hooks);
        $this->assertArrayHasKey('vps.deactivate', $hooks);
        $this->assertArrayHasKey('vps.queue', $hooks);
    }

    /**
     * Verify getHooks() does NOT contain the commented-out activate hook.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksDoesNotContainActivateHook(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayNotHasKey('vps.activate', $hooks);
    }

    /**
     * Verify each hook value is a callable-style array [class, method].
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $this->assertIsArray($value, "Hook '{$key}' value should be an array");
            $this->assertCount(2, $value, "Hook '{$key}' value should have exactly 2 elements");
            $this->assertSame(Plugin::class, $value[0], "Hook '{$key}' first element should be the Plugin class name");
            $this->assertIsString($value[1], "Hook '{$key}' second element should be a string method name");
        }
    }

    /**
     * Verify each hook callback references an existing static method on Plugin.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksCallbackMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $methodName = $value[1];
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "Method '{$methodName}' referenced by hook '{$key}' should exist on Plugin"
            );
            $method = $this->reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isStatic(),
                "Method '{$methodName}' referenced by hook '{$key}' should be static"
            );
        }
    }

    /**
     * Verify getHooks() is idempotent (returns the same result on repeated calls).
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksIsIdempotent(): void
    {
        $first = Plugin::getHooks();
        $second = Plugin::getHooks();
        $this->assertSame($first, $second);
    }

    /**
     * Verify getHooks() is a static method.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Verify the settings hook points to getSettings.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testSettingsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['vps.settings']);
    }

    /**
     * Verify the deactivate hook points to getDeactivate.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testDeactivateHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getDeactivate'], $hooks['vps.deactivate']);
    }

    /**
     * Verify the queue hook points to getQueue.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testQueueHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getQueue'], $hooks['vps.queue']);
    }

    // ---------------------------------------------------------------
    // Event Handler Signature Tests
    // ---------------------------------------------------------------

    /**
     * Verify getActivate accepts exactly one parameter of type GenericEvent.
     *
     * @return void
     */
    public function testGetActivateSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getDeactivate accepts exactly one parameter of type GenericEvent.
     *
     * @return void
     */
    public function testGetDeactivateSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getSettings accepts exactly one parameter of type GenericEvent.
     *
     * @return void
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getQueue accepts exactly one parameter of type GenericEvent.
     *
     * @return void
     */
    public function testGetQueueSignature(): void
    {
        $method = $this->reflection->getMethod('getQueue');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify all event handler methods have void return type or no explicit return type.
     *
     * @return void
     */
    public function testEventHandlerReturnTypes(): void
    {
        $handlers = ['getActivate', 'getDeactivate', 'getSettings', 'getQueue'];
        foreach ($handlers as $handlerName) {
            $method = $this->reflection->getMethod($handlerName);
            $returnType = $method->getReturnType();
            // Event handlers should either have void return or no declared return type
            if ($returnType !== null) {
                $this->assertSame(
                    'void',
                    $returnType->getName(),
                    "Handler '{$handlerName}' should return void if a return type is declared"
                );
            } else {
                // No return type declared is acceptable for event handlers
                $this->assertNull($returnType);
            }
        }
    }

    // ---------------------------------------------------------------
    // Static Analysis: Source Code Inspection
    // ---------------------------------------------------------------

    /**
     * Verify the Plugin source file exists and is readable.
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $filename = $this->reflection->getFileName();
        $this->assertNotFalse($filename);
        $this->assertFileExists($filename);
    }

    /**
     * Verify the source file uses the correct namespace declaration.
     *
     * @return void
     */
    public function testSourceFileNamespaceDeclaration(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString('namespace Detain\MyAdminXen;', $source);
    }

    /**
     * Verify the source file imports GenericEvent from Symfony.
     *
     * @return void
     */
    public function testSourceImportsGenericEvent(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString(
            'use Symfony\Component\EventDispatcher\GenericEvent;',
            $source
        );
    }

    /**
     * Verify event handlers reference global helper functions (myadmin_log, get_service_define)
     * indicating they depend on the MyAdmin framework at runtime.
     *
     * @return void
     */
    public function testEventHandlersUseGlobalHelpers(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString('myadmin_log(', $source);
        $this->assertStringContainsString('get_service_define(', $source);
    }

    /**
     * Verify the getQueue handler references template files via __DIR__.
     *
     * @return void
     */
    public function testGetQueueReferencesTemplates(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString("__DIR__.'/../templates/'", $source);
    }

    /**
     * Verify that event handlers use $event->stopPropagation() for flow control.
     *
     * @return void
     */
    public function testEventHandlersUseStopPropagation(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString('$event->stopPropagation()', $source);
    }

    /**
     * Verify the source references XEN_LINUX and XEN_WINDOWS service type constants.
     *
     * @return void
     */
    public function testSourceReferencesXenServiceTypes(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString("get_service_define('XEN_LINUX')", $source);
        $this->assertStringContainsString("get_service_define('XEN_WINDOWS')", $source);
    }

    /**
     * Verify the source file uses the Detain\Xen\Xen import (dependency on xen library).
     *
     * @return void
     */
    public function testSourceImportsXenClass(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString('use Detain\Xen\Xen;', $source);
    }

    /**
     * Verify getSettings references expected setting keys for Xen VPS configuration.
     *
     * @return void
     */
    public function testGetSettingsReferencesExpectedSettingKeys(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString('vps_slice_xen_cost', $source);
        $this->assertStringContainsString('new_vps_xen_server', $source);
        $this->assertStringContainsString('outofstock_xen', $source);
    }

    /**
     * Verify the source file has a class-level docblock.
     *
     * @return void
     */
    public function testClassHasDocBlock(): void
    {
        $docComment = $this->reflection->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Plugin', $docComment);
    }

    /**
     * Verify all public methods have docblocks.
     *
     * @return void
     */
    public function testAllPublicMethodsHaveDocBlocks(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== Plugin::class) {
                continue;
            }
            $this->assertNotFalse(
                $method->getDocComment(),
                "Public method '{$method->getName()}' should have a docblock"
            );
        }
    }

    // ---------------------------------------------------------------
    // Method Inventory Tests
    // ---------------------------------------------------------------

    /**
     * Verify the Plugin class has the expected set of declared methods.
     *
     * @return void
     */
    public function testExpectedMethodsExist(): void
    {
        $expected = ['__construct', 'getHooks', 'getActivate', 'getDeactivate', 'getSettings', 'getQueue'];
        foreach ($expected as $methodName) {
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "Plugin should have method '{$methodName}'"
            );
        }
    }

    /**
     * Verify the Plugin class does not implement any interfaces (standalone plugin).
     *
     * @return void
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $interfaces = $this->reflection->getInterfaceNames();
        $this->assertEmpty($interfaces);
    }

    /**
     * Verify the Plugin class does not extend any parent class.
     *
     * @return void
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Verify the total count of declared public methods on Plugin.
     *
     * @return void
     */
    public function testPublicMethodCount(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $ownMethods = array_filter($methods, function (ReflectionMethod $m) {
            return $m->getDeclaringClass()->getName() === Plugin::class;
        });
        $this->assertCount(6, $ownMethods, 'Plugin should declare exactly 6 public methods');
    }

    // ---------------------------------------------------------------
    // Template Directory Tests
    // ---------------------------------------------------------------

    /**
     * Verify the templates directory exists relative to the source file.
     *
     * @return void
     */
    public function testTemplatesDirectoryExists(): void
    {
        $srcDir = dirname($this->reflection->getFileName());
        $templatesDir = $srcDir . '/../templates';
        $this->assertDirectoryExists($templatesDir);
    }

    /**
     * Verify at least one .sh.tpl template file exists.
     *
     * @return void
     */
    public function testTemplateFilesExist(): void
    {
        $srcDir = dirname($this->reflection->getFileName());
        $templatesDir = realpath($srcDir . '/../templates');
        $templates = glob($templatesDir . '/*.sh.tpl');
        $this->assertNotEmpty($templates, 'At least one .sh.tpl template should exist');
    }

    /**
     * Verify expected template files are present (delete, enable, start, stop, restart).
     *
     * @return void
     */
    public function testExpectedTemplatesPresent(): void
    {
        $srcDir = dirname($this->reflection->getFileName());
        $templatesDir = realpath($srcDir . '/../templates');
        $expectedTemplates = ['delete.sh.tpl', 'enable.sh.tpl', 'start.sh.tpl', 'stop.sh.tpl', 'restart.sh.tpl'];
        foreach ($expectedTemplates as $template) {
            $this->assertFileExists(
                $templatesDir . '/' . $template,
                "Template '{$template}' should exist"
            );
        }
    }

    // ---------------------------------------------------------------
    // getHooks() Return Type Annotation Test
    // ---------------------------------------------------------------

    /**
     * Verify getHooks() declares an array return type in its docblock.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksDocBlockDeclaresArrayReturn(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $docComment = $method->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('array', $docComment);
    }
}
