<?php namespace App\Web\Controllers;

use App\Json\Schemas\PostSchema as Schema;
use App\Validation\Post\PostCreateForm;
use DateTime;
use Limoncello\Flute\Contracts\Http\Controller\ControllerCreateInterface;
use Limoncello\Flute\Validation\Form\Execution\FormValidatorFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\TextResponse;

/**
 * @package App
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class PostsController extends BaseController implements ControllerCreateInterface
{
    const SUB_URL = '/posts';

    /**
     * @inheritdoc
     */
    public static function create(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $databaseDateFormat = 'Y-m-d H:i:s';

        $validator = (new FormValidatorFactory($container))->createValidator(PostCreateForm::class);
        if ($validator->validate($request->getParsedBody()) === false) {
            return new EmptyResponse(422);
        }

        /** @var DateTime $createdAt */
        [Schema::ATTR_TITLE => $title, Schema::ATTR_TEXT => $text, Schema::ATTR_CREATED_AT => $createdAt] = $validator->getCaptures();

        $createdAt = $createdAt->format($databaseDateFormat);

        return new TextResponse("values($title,$text,$createdAt)");
    }
}
