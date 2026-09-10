<?php

use App\Enums\EvaluationContext;
use App\Enums\EvaluationCriterion;
use App\Enums\EvaluationDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public evaluation guide page is accessible at /guide without authentication', function () {
    $response = $this->get(route('guide'));

    $response->assertStatus(200);
    $response->assertSee('Guide des évaluations');
    $response->assertSee('CA Sion');
    $response->assertSee('Règlement');
});

test('public evaluation guide displays all 9 criteria and their weights', function () {
    $response = $this->get(route('guide'));

    $response->assertStatus(200);

    foreach (EvaluationCriterion::cases() as $criterion) {
        $response->assertSee($criterion->getLabel());
        $response->assertSee($criterion->code());
    }

    $response->assertSee('Poids : 20%'); // C1
    $response->assertSee('Poids : 15%'); // C3, C4, C5
    $response->assertSee('Poids : 10%'); // C6, C7
    $response->assertSee('Poids : 5%');  // C2, C8, C9
});

test('public evaluation guide displays qualitative scale tiers and regulation articles', function () {
    $response = $this->get(route('guide'));

    $response->assertStatus(200);
    $response->assertSee('Non acquis');
    $response->assertSee('En cours d\'acquisition');
    $response->assertSee('Acquis');
    $response->assertSee('Maîtrisé / Très bon');
    $response->assertSee('Exceptionnel');

    // Regulation articles
    $response->assertSee('art. 10.2');
    $response->assertSee('art. 3.4');
    $response->assertSee('Art. 10.4');
    $response->assertSee('Art. 10.5');
});

test('evaluation context and decision enums expose complete regulation and description helpers', function () {
    foreach (EvaluationContext::cases() as $context) {
        expect($context->regulationArticle())->not->toBeEmpty()
            ->and($context->description())->not->toBeEmpty()
            ->and($context->associatedStatusLabel())->not->toBeEmpty();
    }

    foreach (EvaluationDecision::cases() as $decision) {
        expect($decision->description())->not->toBeEmpty()
            ->and($decision->styleConfig())->toBeArray();
    }

    foreach (EvaluationCriterion::cases() as $criterion) {
        expect($criterion->operationalLabel())->not->toBeEmpty()
            ->and($criterion->operationalDetails())->not->toBeEmpty();
    }
});
