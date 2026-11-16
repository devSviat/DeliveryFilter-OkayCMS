<?php

namespace Modules\Sviat\DeliveryFilter;

use Okay\Core\OkayContainer\Reference\ServiceReference;
use Okay\Modules\Sviat\DeliveryFilter\Init\Init;
use PHPUnit\Framework\TestCase;

class ModuleFilesTest extends TestCase
{
    private static function root(): string
    {
        return dirname((string)new \ReflectionClass(Init::class)->getFileName(), 2);
    }

    /** Контейнер передає аргументи за позицією: розбіжність із конструктором падає лише під час запиту. */
    public function testEveryServiceMatchesItsConstructor(): void
    {
        foreach (require self::root() . '/Init/services.php' as $name => $definition) {
            $params = new \ReflectionClass($definition['class'])->getConstructor()?->getParameters() ?? [];
            $arguments = $definition['arguments'] ?? [];
            self::assertCount(count($params), $arguments, $name);
            foreach ($arguments as $i => $argument) {
                $type = $params[$i]->getType();
                self::assertInstanceOf(ServiceReference::class, $argument);
                self::assertInstanceOf(\ReflectionNamedType::class, $type);
                self::assertTrue(is_a($argument->getName(), $type->getName(), true), "{$name} #{$i}");
            }
        }
    }

    public function testAllAdminLanguagesHaveTheSameKeys(): void
    {
        $keys = [];
        foreach (['ua', 'en', 'ru'] as $label) {
            $lang = [];
            include self::root() . "/Backend/lang/{$label}.php";
            $keys[$label] = array_keys($lang);
            sort($keys[$label]);
        }
        self::assertNotEmpty($keys['ua']);
        self::assertSame($keys['ua'], $keys['en']);
        self::assertSame($keys['ua'], $keys['ru']);
    }

    /** Модуль публікується окремим публічним репозиторієм: назв магазину й тем у ньому бути не може. */
    public function testModuleNeverNamesTheShop(): void
    {
        $words = array_values(array_filter(array_map('trim', explode(',', (string)getenv('OKAY_FORBIDDEN_NAMES')))));
        if ($words === []) {
            self::markTestSkipped('OKAY_FORBIDDEN_NAMES порожня');
        }
        $hits = [];
        $base = dirname(__DIR__, 4);
        foreach ([self::root(), __DIR__] as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                foreach ($words as $word) {
                    if (stripos(substr($file->getPathname(), strlen($base)) . "\n" . file_get_contents($file->getPathname()), $word) !== false) {
                        $hits[] = $file->getFilename();
                    }
                }
            }
        }
        self::assertSame([], $hits);
    }

    /** Без таблиць, налаштувань і типу модуля: увімкнути й вимкнути хаб можна будь-коли. */
    public function testInstallTouchesNothing(): void
    {
        $install = new \ReflectionMethod(Init::class, 'install');
        $body = implode('', array_slice(file(self::root() . '/Init/Init.php'), $install->getStartLine() - 1, $install->getEndLine() - $install->getStartLine() + 1));

        self::assertStringNotContainsString('$this->', $body);
    }
}
