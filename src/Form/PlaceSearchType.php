<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Single-field form: a place search rendered as a remote UX Autocomplete.
 *
 * Options are fed by the `geocode_search` endpoint; each option's value is the
 * `"latitude,longitude"` of the geocoded place, so the submitted data is ready to be
 * turned into forecast route parameters.
 *
 * @extends AbstractType<array{place?: string|null}>
 */
final class PlaceSearchType extends AbstractType
{
    /**
     * Matches the `"lat,lon"` value produced by the geocode endpoint.
     */
    public const string LAT_LON_PATTERN = '/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // TextType (not ChoiceType) keeps this simple: the UX Autocomplete extension renders the
        // remote TomSelect widget and the submitted value is the raw "lat,lon" string — no choice
        // list, so no custom choice loader is needed.
        $builder->add('place', TextType::class, [
            'label' => 'homepage.search.label',
            'autocomplete' => true,
            'autocomplete_url' => $this->urlGenerator->generate('geocode_search'),
            'tom_select_options' => [
                'create' => false,
                'maxItems' => 1,
            ],
            'constraints' => [
                new NotBlank(message: 'homepage.search.required'),
                new Regex(pattern: self::LAT_LON_PATTERN, message: 'homepage.search.invalid'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => Request::METHOD_GET,
        ]);
    }
}
