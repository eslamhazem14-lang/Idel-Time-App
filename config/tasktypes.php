<?php

use App\TaskTypes\Types\AiEvaluationType;
use App\TaskTypes\Types\BugReproductionType;
use App\TaskTypes\Types\CodeReviewType;
use App\TaskTypes\Types\DocumentationVerificationType;
use App\TaskTypes\Types\MultipleChoiceType;
use App\TaskTypes\Types\TextResponseType;
use App\TaskTypes\Types\WebsiteQaType;

/*
| Registered task types. Add a class implementing App\TaskTypes\TaskTypeHandler
| plus its Blade partials (resources/views/task-types/{key}/) to extend the platform.
*/

return [
    TextResponseType::class,
    MultipleChoiceType::class,
    CodeReviewType::class,
    WebsiteQaType::class,
    AiEvaluationType::class,
    DocumentationVerificationType::class,
    BugReproductionType::class,
];
