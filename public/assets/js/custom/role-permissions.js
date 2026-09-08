(function () {
    "use strict";

    function initializePermissionMatrix(matrix) {
        const permissionCheckboxes = function () {
            return Array.from(matrix.querySelectorAll(".permission-checkbox"));
        };

        const editableCheckboxes = function (container) {
            return Array.from(
                (container || matrix).querySelectorAll(
                    ".permission-checkbox:not(:disabled)"
                )
            );
        };

        const applyPermissionDependencies = function (checkbox) {
            const module = checkbox.dataset.permissionModule;
            const action = checkbox.dataset.permissionAction;

            if (action !== "view" && checkbox.checked) {
                const viewPermission = matrix.querySelector(
                    '.permission-checkbox[data-permission-module="' +
                        module +
                        '"][data-permission-action="view"]'
                );

                if (viewPermission) {
                    viewPermission.checked = true;
                }
            }

            if (action === "view" && !checkbox.checked) {
                matrix
                    .querySelectorAll(
                        '.permission-checkbox[data-permission-module="' +
                            module +
                            '"]:not([data-permission-action="view"]):not(:disabled)'
                    )
                    .forEach(function (modulePermission) {
                        modulePermission.checked = false;
                    });
            }
        };

        const updateState = function () {
            const allPermissions = permissionCheckboxes();
            const selectedPermissions = allPermissions.filter(function (
                checkbox
            ) {
                return checkbox.checked;
            });
            const selectedCount = matrix.querySelector(
                "[data-selected-permission-count]"
            );

            if (selectedCount) {
                selectedCount.textContent = selectedPermissions.length;
            }

            matrix
                .querySelectorAll("[data-permission-group]")
                .forEach(function (group) {
                    const allGroupPermissions = Array.from(
                        group.querySelectorAll(".permission-checkbox")
                    );
                    const optionalPermissions = editableCheckboxes(group);
                    const checkedOptionalPermissions =
                        optionalPermissions.filter(function (checkbox) {
                            return checkbox.checked;
                        });
                    const groupCount = group.querySelector(
                        "[data-group-selected-count]"
                    );
                    const groupToggle = group.querySelector(
                        ".permission-group-toggle"
                    );

                    if (groupCount) {
                        groupCount.textContent = allGroupPermissions.filter(
                            function (checkbox) {
                                return checkbox.checked;
                            }
                        ).length;
                    }

                    if (groupToggle) {
                        groupToggle.checked =
                            optionalPermissions.length > 0 &&
                            checkedOptionalPermissions.length ===
                                optionalPermissions.length;
                        groupToggle.indeterminate =
                            checkedOptionalPermissions.length > 0 &&
                            checkedOptionalPermissions.length <
                                optionalPermissions.length;
                    }
                });
        };

        matrix.addEventListener("change", function (event) {
            if (event.target.classList.contains("permission-group-toggle")) {
                const group = event.target.closest("[data-permission-group]");
                editableCheckboxes(group).forEach(function (checkbox) {
                    checkbox.checked = event.target.checked;
                });
                updateState();
                return;
            }

            if (event.target.classList.contains("permission-checkbox")) {
                applyPermissionDependencies(event.target);
                updateState();
            }
        });

        matrix.addEventListener("click", function (event) {
            const actionButton = event.target.closest(
                "[data-permission-control]"
            );
            if (!actionButton) return;

            const shouldSelect =
                actionButton.dataset.permissionControl === "select-all";
            editableCheckboxes().forEach(function (checkbox) {
                checkbox.checked = shouldSelect;
            });
            updateState();
        });

        const searchInput = matrix.querySelector("[data-permission-search]");
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                const search = searchInput.value.trim().toLowerCase();
                let visibleGroups = 0;

                matrix
                    .querySelectorAll("[data-permission-group-column]")
                    .forEach(function (column) {
                        let visiblePermissions = 0;

                        column
                            .querySelectorAll("[data-permission-option]")
                            .forEach(function (option) {
                                const visible =
                                    search === "" ||
                                    option.dataset.searchText.includes(search);
                                option.classList.toggle("d-none", !visible);
                                if (visible) visiblePermissions += 1;
                            });

                        column.classList.toggle(
                            "d-none",
                            visiblePermissions === 0
                        );
                        if (visiblePermissions > 0) visibleGroups += 1;
                    });

                const noResults = matrix.querySelector(
                    "[data-permission-no-results]"
                );
                if (noResults) {
                    noResults.classList.toggle("d-none", visibleGroups > 0);
                }
            });
        }

        updateState();
    }

    const initialize = function () {
        document
            .querySelectorAll("[data-permission-matrix]")
            .forEach(initializePermissionMatrix);
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initialize);
    } else {
        initialize();
    }
})();
