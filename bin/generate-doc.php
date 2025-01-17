<?php
// bin/generate-doc.php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Finder\Finder;

class DocumentationGenerator
{
    private string $outputDir;

    public function __construct()
    {
        $this->outputDir = __DIR__ . '/../docs/api';
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    public function generate(): void
    {
        $finder = new Finder();
        $finder->files()
               ->in(__DIR__ . '/../src')
               ->name('*.php');

        $documentation = [];
        
        foreach ($finder as $file) {
            $className = $this->getClassName($file);
            if (class_exists($className)) {
                $documentation[$className] = $this->documentClass($className);
            }
        }

        $this->generateHtml($documentation);
    }

    private function getClassName($file): string
    {
        $namespace = 'App';
        $path = $file->getRelativePathname();
        return $namespace . '\\' . str_replace(
            ['/', '.php'],
            ['\\', ''],
            $path
        );
    }

    private function documentClass(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        
        return [
            'name' => $reflection->getShortName(),
            'namespace' => $reflection->getNamespaceName(),
            'comment' => $reflection->getDocComment(),
            'methods' => $this->documentMethods($reflection),
        ];
    }

    private function documentMethods(\ReflectionClass $class): array
    {
        $methods = [];
        foreach ($class->getMethods() as $method) {
            $methods[] = [
                'name' => $method->getName(),
                'comment' => $method->getDocComment(),
                'visibility' => $this->getVisibility($method),
                'parameters' => $this->getParameters($method),
            ];
        }
        return $methods;
    }

    private function getVisibility(\ReflectionMethod $method): string
    {
        if ($method->isPublic()) return 'public';
        if ($method->isProtected()) return 'protected';
        return 'private';
    }

    private function getParameters(\ReflectionMethod $method): array
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $typeName = 'mixed';
            
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
            } elseif ($type instanceof \ReflectionUnionType) {
                $typeNames = array_map(function($t) {
                    return $t->getName();
                }, $type->getTypes());
                $typeName = implode('|', $typeNames);
            }
            
            $params[] = [
                'name' => $param->getName(),
                'type' => $typeName,
            ];
        }
        return $params;
    }

    private function generateHtml(array $documentation): void
    {
        $html = $this->getHtmlHeader();
        
        foreach ($documentation as $className => $classData) {
            $html .= $this->generateClassHtml($className, $classData);
        }
        
        $html .= $this->getHtmlFooter();
        
        file_put_contents($this->outputDir . '/index.html', $html);
    }

    private function generateClassHtml(string $className, array $classData): string
    {
        $html = "<h2 class='class-name'>{$classData['name']}</h2>";
        $html .= "<p class='namespace'>{$classData['namespace']}</p>";
        
        if ($classData['comment']) {
            $html .= "<div class='class-doc'>" . $this->formatDocComment($classData['comment']) . "</div>";
        }
        
        $html .= "<h3>Méthodes</h3>";
        foreach ($classData['methods'] as $method) {
            $html .= $this->generateMethodHtml($method);
        }
        
        return $html;
    }

    private function generateMethodHtml(array $method): string
    {
        $html = "<div class='method'>";
        $html .= "<h4>{$method['visibility']} {$method['name']}</h4>";
        
        if ($method['comment']) {
            $html .= "<div class='method-doc'>" . $this->formatDocComment($method['comment']) . "</div>";
        }
        
        if (!empty($method['parameters'])) {
            $html .= "<p>Paramètres:</p><ul>";
            foreach ($method['parameters'] as $param) {
                $html .= "<li>{$param['type']} \${$param['name']}</li>";
            }
            $html .= "</ul>";
        }
        
        $html .= "</div>";
        return $html;
    }

    private function formatDocComment(?string $comment): string
    {
        if (!$comment) return '';
        return nl2br(trim(preg_replace('/^\s*\*\s*/m', '', trim($comment, '/*'))));
    }

    private function getHtmlHeader(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Documentation API</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .class-name { color: #2c3e50; }
                .namespace { color: #7f8c8d; }
                .method { margin: 20px 0; padding: 10px; background: #f8f9fa; }
                .method-doc { margin: 10px 0; }
            </style>
        </head>
        <body>
            <h1>Documentation API</h1>
        HTML;
    }

    private function getHtmlFooter(): string
    {
        return "</body></html>";
    }
}

// Générer la documentation
$generator = new DocumentationGenerator();
$generator->generate();

echo "Documentation générée avec succès dans le dossier docs/api\n";