<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Actions;

use DomainException;
use Lemonade\Admin\Action\Contract\ModuleActionAccessPolicyInterface;
use Lemonade\Admin\Action\Contract\ModuleActionHandlerInterface;
use Lemonade\Admin\Action\Exception\ModuleActionException;
use Lemonade\Admin\Action\ModuleActionResult;
use Lemonade\Admin\Authorization\AuthorizationService;
use Lemonade\Admin\Http\AdminErrorCode;
use Lemonade\Admin\Identity\LocalActorGuard;
use Lemonade\Admin\Identity\LocalActorRequiredException;
use Lemonade\Cms\Auction\AuctionData;
use Lemonade\Cms\Auction\Models\AuctionModel;
use Lemonade\Cms\Content\CmsContentLifecycle;
use Lemonade\Framework\Http\HttpStatus as HttpStatusCode;

/**
 * Prevadi soft delete Dražby na canonical Admin action response
 */
final readonly class AuctionDeleteAction implements ModuleActionHandlerInterface, ModuleActionAccessPolicyInterface
{
    /**
     * Nastavuje Auction lifecycle a localni identitu auditovane mutace
     *
     * @param CmsContentLifecycle<AuctionData> $auction
     */
    public function __construct(
        private CmsContentLifecycle $auction,
        private LocalActorGuard $actors,
        private AuctionModel $model,
        private AuthorizationService $authorization,
    ) {}

    /**
     * Overi soft delete globalnim nebo vlastnickym opravnenim
     */
    public function accessDecision(?int $id): bool
    {
        return $id !== null && $this->authorization->canForOwner(
            'cms.auction.delete',
            'cms.auction.delete_own',
            $this->model->ownerId($id),
        );
    }

    /**
     * Provede soft delete a prevede authorization chybu do Admin transportu
     *
     * @param array<string,mixed> $payload
     */
    public function execute(?int $id, array $payload): ModuleActionResult
    {
        if ($id === null) {
            throw new ModuleActionException(
                HttpStatusCode::NOT_FOUND,
                AdminErrorCode::NOT_FOUND,
                'Auction item not found.',
            );
        }

        try {
            $this->auction->delete(
                $this->actors->requireLocalUser()->id(),
                $id,
            );
        } catch (LocalActorRequiredException $exception) {
            throw new ModuleActionException(
                HttpStatusCode::FORBIDDEN,
                AdminErrorCode::LOCAL_ACTOR_REQUIRED,
                $exception->getMessage(),
            );
        } catch (DomainException $exception) {
            return ModuleActionResult::invalid(['_form' => $exception->getMessage()]);
        }

        return ModuleActionResult::success('auction.actions.deleted');
    }
}
