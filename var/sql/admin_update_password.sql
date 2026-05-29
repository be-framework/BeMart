UPDATE dtb_member
SET password = :passwordHash,
    update_date = NOW()
WHERE id = CAST(:adminId AS UNSIGNED)
