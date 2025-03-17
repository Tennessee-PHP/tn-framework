<?php

namespace TN\TN_Core\Error\CodeGenerator;

enum CodeGeneratorErrorMessage: string {
    case PackageReflection = 'Package selected is missing its Package.php file';
    case DirectoryNotWritable = 'Directory is not writable';
    case FileWriteError = 'Failed to write file';
    case FileExists = 'File already exists';
    case ControllerClassNotFound = 'Controller class not found when parsing file';
}