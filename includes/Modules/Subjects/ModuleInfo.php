<?php namespace geminorum\gEditorial\Modules\Subjects;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleInfo extends gEditorial\Info
{
	const MODULE = 'subjects';

	/**
	 * List of subjects commonly explored in literature.
	 *
	 * Common subjects that literature deals with encompass a wide range
	 * of themes reflecting the human experience. These include love and
	 * relationships, identity, and self-discovery, social, and political
	 * commentary, nature, and the environment, mortality, and existentialism,
	 * and conflict and resilience. Through exploring these subjects,
	 * literature offers profound insights into the complexities of life,
	 * inviting readers to engage with universal truths and the intricacies
	 * of human existence.
	 *
	 * @source https://www.quora.com/What-are-the-common-subjects-that-deal-with-literature
	 *
	 * @param string $context
	 * @return array
	 */
	public static function getLiterarySubjects( $context = NULL )
	{
		return [
			'love-relationships' => [
				'slug'  => 'love-relationships',
				'name'  => _x( 'Love and Relationships', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '1',
			],
			'identity-selfdiscovery' => [
				'slug'  => 'identity-selfdiscovery',
				'name'  => _x( 'Identity and Self-Discovery', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '2',
			],
			'social-political' => [
				'slug'  => 'social-political',
				'name'  => _x( 'Social and Political Commentary', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '3',
			],
			'nature-environment' => [
				'slug'  => 'nature-environment',
				'name'  => _x( 'Nature and the Environment', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '4',
			],
			'mortality-existentialism' => [
				'slug'  => 'mortality-existentialism',
				'name'  => _x( 'Mortality and Existentialism', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '5',
			],
			'conflict-resilience' => [
				'slug'  => 'conflict-resilience',
				'name'  => _x( 'Conflict and Resilience', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '6',
			],
			'history-memory' => [
				'slug'  => 'history-memory',
				'name'  => _x( 'History and Memory', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '7',
			],
			'culture-tradition' => [
				'slug'  => 'culture-tradition',
				'name'  => _x( 'Culture and Tradition', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '8',
			],
			'power-authority' => [
				'slug'  => 'power-authority',
				'name'  => _x( 'Power and Authority', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '9',
			],
			'technology-society' => [
				'slug'  => 'technology-society',
				'name'  => _x( 'Technology and Society', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '10',
			],
			'art-creativity' => [
				'slug'  => 'art-creativity',
				'name'  => _x( 'Art and Creativity', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '11',
			],
			'freedom-oppression' => [
				'slug'  => 'freedom-oppression',
				'name'  => _x( 'Freedom and Oppression', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '12',
			],
			'faith-religion' => [
				'slug'  => 'faith-religion',
				'name'  => _x( 'Faith and Religion', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '13',
			],
			'justice-injustice' => [
				'slug'  => 'justice-injustice',
				'name'  => _x( 'Justice and Injustice', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '14',
			],
			'education-knowledge' => [
				'slug'  => 'education-knowledge',
				'name'  => _x( 'Education and Knowledge', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '15',
			],
			'wealth-poverty' => [
				'slug'  => 'wealth-poverty',
				'name'  => _x( 'Wealth and Poverty', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '16',
			],
			'friendship-loyalty' => [
				'slug'  => 'friendship-loyalty',
				'name'  => _x( 'Friendship and Loyalty', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '17',
			],
			'dreams-ambitions' => [
				'slug'  => 'dreams-ambitions',
				'name'  => _x( 'Dreams and Ambitions', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '18',
			],
			'mentalhealth-wellbeing' => [
				'slug'  => 'mentalhealth-wellbeing',
				'name'  => _x( 'Mental Health and Well-being', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '19',
			],
			'diversity-inclusion' => [
				'slug'  => 'diversity-inclusion',
				'name'  => _x( 'Diversity and Inclusion', 'Literary Subject: Name', 'geditorial-subjects' ),
				'order' => '20',
			],
		];
	}
}
