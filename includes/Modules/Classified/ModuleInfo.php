<?php namespace geminorum\gEditorial\Modules\Classified;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'classified';

	public static function getClassifications( $context = NULL )
	{
		switch ( Core\L10n::locale( TRUE ) ) {

			case 'fa_IR':

				// @REF: https://isosystem.org/information-classification-iso-27001/
				return [
					'public' => [
						'slug'        => 'public',
						'name'        => 'عمومی',
						'description' => 'اطلاعاتی است که تمام افراد در سازمان و خارج از آن به آن دسترسی دارند.',
					],
					'internal' => [
						'slug'        => 'internal',
						'name'        => 'داخلی',
						'description' => 'اطلاعاتی است که تمام کارکنان به آن دسترسی دارند.',
					],
					'restricted' => [
						'slug'        => 'restricted',
						'name'        => 'محدود',
						'description' => 'نشان دهنده تمام اطلاعاتی است که در دسترس اکثر کارکنان است؛ اما نه برای تمام آن‌ها.',
					],
					'classified' => [
						'slug'        => 'classified',
						'name'        => 'طبقه‌بندی‌شده',
						'description' => 'اطلاعات حساسی است که دسترسی به آن‌ها توسط قانون یا مقررات محدود شده است. هنگامی که هر یک از طرفین دارای اطلاعات طبقه‌بندی شده باشد، برای رسیدگی به چنین اطلاعاتی به مجوز رسمی امنیتی نیاز است.',
					],
					'confidential' => [
						'slug'        => 'confidential',
						'name'        => 'محرمانه',
						'description' => 'اطلاعات محرمانه‌ توسط همه طرف‌های شامل یا تحت تأثیر آن اطلاعات حفظ می‌شود. گاهی اوقات از اصطلاحات اطلاعات محرمانه و اطلاعات طبقه‌بندی شده در یک زمینه استفاده می‌شود. باوجوداین، اطلاعات طبقه‌بندی شده در واقع بیشتر توسط نهادهای دولتی به‌عنوان یک اصطلاح حقوقی به‌کار می‌رود.',
					],
				];

			default:

				/**
				 * ISO Information Classification Levels
				 *
				 * Based on the provided search results, here is a summary of the ISO information classification levels mentioned:
				 * 1. **Confidential**: Typically used for sensitive information that requires significant security measures with strictly controlled and limited access. (Example: University of Bath's classification scheme)
				 * 2. **Internal**: Information that is only accessible to internal parties with specific permission. (Example: business plans and memorandums)
				 * 3. **Public**: Information that can be shared and accessed by anyone without any consequences. (Example: first and last names, press releases)
				 * 4. **Restricted**: Information that requires protection based on its sensitivity and importance, with access restricted to authorized personnel. (Example: patient names, dates of birth, social security numbers, medical records)
				 * 5. **Secret**: Information that requires high-level security measures and access is limited to those with a need-to-know basis. (Example: not explicitly mentioned, but implied as a higher level of classification)
				 * 6. **Top Secret**: Information that requires the highest level of security and access is extremely limited, typically only available to a select few with the highest clearance. (Example: not explicitly mentioned, but implied as an even higher level of classification)
				 *
				 * Additionally, some sources suggest using a simpler classification scheme with only three levels:
				 * 1. **Confidential**: Sensitive information that requires protection.
				 * 2. **Internal**: Information that is only accessible to internal parties.
				 * 3. **Public**: Information that is publicly available.
				 * It's essential to note that the specific classification levels and schemes may vary depending on the organization, industry, and jurisdiction. The ISO 27001 standard does not prescribe a specific classification scheme, but rather emphasizes the importance of classifying information according to the organization's information security needs and relevant interested party requirements.
				 *
				 * In practice, organizations may choose to use a combination of these levels or develop their own classification scheme based on their unique requirements and risk assessments. It's crucial to document the classification scheme and ensure that all employees understand the classification levels and their associated access controls.
				 */

				/**
				 * Confidential (top confidentiality level)
				 * Restricted (medium confidentiality level)
				 * Internal use (lowest level of confidentiality)
				 * Public (everyone can see the information)
				 * @source https://advisera.com/27001academy/blog/2014/05/12/information-classification-according-to-iso-27001/
				 */

				return [
					'confidential' => [
						'slug'        => 'confidential',
						'name'        => 'Confidential',
						'description' => 'Typically used for sensitive information that requires significant security measures with strictly controlled and limited access.',
						// Example: University of Bath's classification scheme: https://www.bath.ac.uk/corporate-information/information-classification-scheme/
					],
					'internal' => [
						'slug'        => 'internal',
						'name'        => 'Internal',
						'description' => 'Information that is only accessible to internal parties with specific permission.',
						// Example: business plans and memorandums
					],
					'public' => [
						'slug'        => 'public',
						'name'        => 'Public',
						'description' => 'Information that can be shared and accessed by anyone without any consequences.',
						// Example: first and last names, press releases
					],
					'restricted' => [
						'slug'        => 'restricted',
						'name'        => 'Restricted',
						'description' => 'Information that requires protection based on its sensitivity and importance, with access restricted to authorized personnel.',
						// Example: patient names, dates of birth, social security numbers, medical records
					],
					'secret' => [
						'slug'        => 'secret',
						'name'        => 'Secret',
						'description' => 'Information that requires high-level security measures and access is limited to those with a need-to-know basis.',
						// Example: not explicitly mentioned, but implied as a higher level of classification
					],
					'top-secret' => [
						'slug'        => 'top-secret',
						'name'        => 'Top Secret',
						'description' => 'Information that requires the highest level of security and access is extremely limited, typically only available to a select few with the highest clearance.',
						// Example: not explicitly mentioned, but implied as an even higher level of classification
					],
				];
		}
	}
}
