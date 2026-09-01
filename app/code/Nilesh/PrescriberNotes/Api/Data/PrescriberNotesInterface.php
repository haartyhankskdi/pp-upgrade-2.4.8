<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Api\Data;

interface PrescriberNotesInterface
{

    const NOTE = 'note';
    const CONNECTID = 'connect_id';
    const PRESCRIBERNOTES_ID = 'prescribernotes_id';
    const CREATED_AT = 'created_at';
    const CUSTOMER_NAME = 'customer_name';
    const SUBJECT = 'subject';
    const CREATED_BY = 'created_by';

    /**
     * Get prescribernotes_id
     * @return string|null
     */
    public function getPrescribernotesId();

    /**
     * Set prescribernotes_id
     * @param string $prescribernotesId
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setPrescribernotesId($prescribernotesId);

    /**
     * Get id
     * @return string|null
     */
    public function getConnectId();

    /**
     * Set id
     * @param string $id
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setConnectId($id);

    /**
     * Get subject
     * @return string|null
     */
    public function getSubject();

    /**
     * Set subject
     * @param string $subject
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setSubject($subject);

    /**
     * Get note
     * @return string|null
     */
    public function getNote();

    /**
     * Set note
     * @param string $note
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setNote($note);

    /**
     * Get customer_name
     * @return string|null
     */
    public function getCustomerName();

    /**
     * Set customer_name
     * @param string $customerName
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setCustomerName($customerName);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get created_by
     * @return string|null
     */
    public function getCreatedBy();

    /**
     * Set created_by
     * @param string $createdBy
     * @return \Nilesh\PrescriberNotes\PrescriberNotes\Api\Data\PrescriberNotesInterface
     */
    public function setCreatedBy($createdBy);
}

