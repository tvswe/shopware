<?php

declare(strict_types=1);
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Tests\Mink\Page;

use SensioLabs\Behat\PageObjectExtension\PageObject\Page;
use Shopware\Tests\Mink\Helper;
use Shopware\Tests\Mink\HelperSelectorInterface;

class GenericPage extends Page implements HelperSelectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCssSelectors()
    {
        return [
            'canonical' => 'link[rel=canonical]',
            'next' => 'link[rel=next]',
            'prev' => 'link[rel=prev]',
            'robots' => 'meta[name=robots]',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getNamedSelectors()
    {
        return [];
    }

    /**
     * Checks if the canonical/next/prev links matches the given path and query
     * Fails validation if the matches are not exact for either argument
     * If null arguments are provided, no next page link is expected, and validation
     * will fail if one is found
     *
     * @param string      $locator
     * @param string|null $path
     * @param array       $query
     */
    public function checkLink($locator, $path = null, $query = [])
    {
        $elements = Helper::findElements($this, [$locator], false);

        if ($path !== null && empty($elements[$locator])) {
            Helper::throwException(['Link expected but not found while looking for ' . $locator]);
        } elseif ($path === null && !empty($elements[$locator])) {
            Helper::throwException(['Link not expected but found while looking for ' . $locator]);
        } elseif ($path === null && empty($elements[$locator])) {
            return;
        }

        $link = $elements[$locator]->getAttribute('href');
        $linkParts = parse_url($link);

        $expectedUrl = rtrim($this->getParameter('base_url') ?? '', '/') . '/' . rtrim($path ?? '', '/');
        if (!str_contains($expectedUrl, '?')) {
            $expectedUrl .= '/';
        }
        if (!empty($query)) {
            $expectedUrl .= '?' . http_build_query($query);
        }

        $expectedUrlParts = parse_url($expectedUrl);

        if ($linkParts != $expectedUrlParts) {
            $message = sprintf(
                'Link "%s" does not match expected value "%s" while looking for ' . $locator,
                $link,
                $expectedUrl
            );

            Helper::throwException([$message]);
        }
    }

    /**
     * Checks if the robots meta exists and matches the expected content
     *
     * @param array $content
     */
    public function checkRobots($content = [])
    {
        $elements = Helper::findElements($this, ['robots']);
        $robotsValue = $elements['robots']->getAttribute('content');
        if (empty($robotsValue)) {
            Helper::throwException(['Missing robots data']);
        }
        $robotsParts = explode(',', $robotsValue);

        $robotsParts = array_map('trim', $robotsParts);

        if ($robotsParts != $content) {
            $message = sprintf(
                'Canonical link "%s" does not match expected value "%s"',
                implode(', ', $robotsParts),
                implode(', ', $content)
            );

            Helper::throwException([$message]);
        }
    }
}
